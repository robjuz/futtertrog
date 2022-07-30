<?php

namespace App;

use App\Casts\MealProviderCast;
use App\MealProviders\AbstractMealProvider;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
 * @property-read \App\MealCollection|\App\Meal[] $meals
 * @property-read int|null $meals_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
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
            $order->previous_status = $order->original['status'] ?? null;
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

//
//    public function users()
//    {
//        return $this->hasManyThrough(Meal::class, OrderItem::class, 'order_id', 'id', 'id', 'user_id');
//    }

    public function getSubtotalAttribute()
    {
        return Money::sum(
            Money::parse(0),
            ...$this->orderItems->map->subtotal
        );
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

    public function canBeAutoOrdered()
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

    public function canBeUpdated()
    {

        if (! $this->canBeAutoOrdered()) {
            return false;
        }

        if (!$this->external_id) {
            return false;
        }

        return self::where('external_id', '>', $this->external_id)
                ->whereProvider($this->provider->getKey())
                ->doesntExist();
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

    public function placeOrder() {
        $this->provider->placeOrder($this);
    }

    public function updateOrder() {
        $this->provider->updateOrder($this);
    }
}
