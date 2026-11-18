<?php

namespace App\Models;

use App\Casts\MealProviderCast;
use App\Actions\Admin\ActivityLogger;
use App\IdeHelperOrder;
use App\MealProviders\AbstractMealProvider;
use App\OrderCollection;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * App\Order.
 *
 * @property int $id
 * @property AbstractMealProvider|null $provider
 * @property string $status
 * @property string $previous_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $external_id
 * @property-read mixed $is_open
 * @property-read mixed $subtotal
 * @property-read \App\MealCollection|\App\Models\Meal[] $meals
 * @property-read int|null $meals_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @method static \App\OrderCollection|static[] all($columns = ['*'])
 * @method static \App\OrderCollection|static[] get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @mixin IdeHelperOrder
 */
class Order extends Model
{
    use HasFactory;

    const STATUS_OPEN = 'open';

    const STATUS_ORDERED = 'ordered';

    public static $statuses = [
        self::STATUS_OPEN,
        self::STATUS_ORDERED,
    ];

    protected $casts = [
        'provider' => MealProviderCast::class
    ];

    protected $guarded = [];

    protected $appends = ['subtotal'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $order) {
            $order->previous_status = $order->getOriginal('status');
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function meals()
    {
        return $this->hasManyThrough(Meal::class, OrderItem::class, 'order_id', 'id', 'id', 'meal_id');
    }

    public function payedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

//
//    public function users()
//    {
//        return $this->hasManyThrough(Meal::class, OrderItem::class, 'order_id', 'id', 'id', 'user_id');
//    }

    public function getSubtotalAttribute()
    {
        if (empty($this->attributes['subtotal'])) {
            $this->loadMissing('orderItems.meal');

            $this->attributes['subtotal'] = Money::sum(
                   Money::parse(0),
                ...$this->orderItems->map->subtotal
            );
        }

        return $this->attributes['subtotal'];
    }

    public function getIsPayedAttribute(): bool
    {
        return (bool)($this->payed_at ?? $this->payedBy);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \App\MealCollection
     */
    public function newCollection(array $models = [])
    {
        return new OrderCollection($models);
    }

    public function canBeAutoOrdered(): bool
    {
        if (! $this->provider) {
            return false;
        }

        if (! $this->provider->supportsAutoOrder() ?? false) {
            return false;
        }

        if ($this->status === self::STATUS_ORDERED) {
            return false;
        }

        if ($this->meals()->whereNull('external_id')->exists()) {
            return false;
        }

        return true;
    }

    public function canBeUpdated(): bool
    {

        if (! $this->canBeAutoOrdered()) {
            return false;
        }

        if (!$this->external_id) {
            return false;
        }

        return true;
    }

    public function reopen()
    {
        $this->update(
            [
                'status' => Order::STATUS_OPEN,
            ]
        );

        return $this;
    }

    public function markOrdered()
    {
        $this->update(
            [
                'status' => Order::STATUS_ORDERED,
            ]
        );

        return $this;
    }


    public function getIsOpenAttribute()
    {
        return $this->status === Order::STATUS_OPEN;
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), ['provider' => $this->provider->__toString()]);
    }

    public function wasReopened()
    {
        return $this->previous_status === self::STATUS_ORDERED and $this->status === self::STATUS_OPEN;
    }

    public function getFormattedDate()
    {
        return implode(' - ', [
            Carbon::parse($this->meals_min_date)->isoFormat('L'),
            Carbon::parse($this->meals_max_date)->isoFormat('L')
        ]);
    }

    /** A committed reservation protects both scheduled and administrative provider calls. */
    public function autoOrder(?User $actor = null): self
    {
        $attempt = DB::transaction(function () use ($actor): ?AdminActivity {
            $order = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($actor !== null) {
                $actor = ActivityLogger::authorize($actor);
                Gate::forUser($actor)->authorize('autoOrder', $order);
            }
            $blocked = AdminActivity::where('action', 'order.auto_ordered')
                ->where('target_type', self::class)->where('target_id', $order->id)
                ->whereIn('state', ['running', 'uncertain'])->exists();
            abort_if($blocked, 409, __('Provider result is pending or uncertain. Reconcile with the provider before retrying.'));
            if (!$order->canBeAutoOrdered()) {
                abort_if($actor !== null, 400, __('This order cannot be auto-ordered'));

                return null;
            }
            if (DB::transactionLevel() !== 1) {
                throw new \LogicException('Autoorder requires a committed reservation; do not wrap it in a transaction.');
            }
            $activity = ActivityLogger::record($actor, 'order.auto_ordered', $order, ['status' => $order->status]);
            $activity->update(['state' => 'running']);

            return $activity;
        });
        if ($attempt === null) {
            return $this->refresh();
        }

        try {
            DB::transaction(function () use ($attempt): void {
                $order = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
                if ($order->canBeUpdated()) {
                    $order->updateOrder();
                } else {
                    $order->placeOrder();
                }
                abort_unless($order->fresh()->status === self::STATUS_ORDERED, 502, __('Provider result is uncertain.'));
                $attempt->update([
                    'state' => 'succeeded',
                    'changes' => ActivityLogger::changes('order.auto_ordered', $attempt->getAttribute('changes')['before'], ['status' => $order->status]),
                ]);
            });
        } catch (Throwable $exception) {
            $attempt->update(['state' => 'uncertain']);
            throw $exception;
        }

        return $this->refresh();
    }

    public function placeOrder() {
        $this->provider->placeOrder($this);
    }

    public function updateOrder() {
        $this->provider->updateOrder($this);
    }
}
