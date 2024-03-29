<?php

namespace App;

use App\MealProviders\AbstractMealProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * App\Order.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
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
 *
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
 */
class Order extends Model
{
    const STATUS_OPEN = 'open';

    const STATUS_ORDERED = 'ordered';

    public static $statuses = [
        self::STATUS_OPEN,
        self::STATUS_ORDERED,
    ];

    protected $guarded = [];

    protected $dates = ['date'];

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
        return $this->orderItems->sum->subtotal;
    }

    public function orderItemsCompact()
    {
        $orderItems = $this->orderItems->groupBy('meal_id');

        $orderItemsGrouped = [];

        foreach ($orderItems as $key => $mealGroup) {
            $orderItemsGrouped[$key]['quantity'] = 0;
            foreach ($mealGroup as $orderItem) {
                $orderItemsGrouped[$key]['meal'] = $orderItem->meal;
                $orderItemsGrouped[$key]['users'][] = $orderItem->user;
                $orderItemsGrouped[$key]['quantity'] += $orderItem->quantity;
            }

            $orderItemsGrouped = array_values(Arr::sort($orderItemsGrouped, function ($value) {
                return $value['meal']->id;
            }));
        }

        return collect($orderItemsGrouped)->map(function ($item) {
            return new OrderItemCompact($item['meal'], $item['users'], $item['quantity']);
        });
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

        return Auth::user()->can('create', [OrderItem::class, request()->date]);
    }

    public function canBeUpdated()
    {
        if (! $this->provider) {
            return false;
        }

        if (! $this->provider->supportsOrderUpdate() ?? false) {
            return false;
        }

        return
            (bool) $this->external_id
            and self::where('external_id', '>', $this->external_id)
                ->whereProvider($this->provider)
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

    public function getProviderAttribute($value)
    {
        try {
            return app()->make($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), ['provider' => $this->provider->__toString()]);
    }

    public function wasReopened()
    {
        return $this->previous_status === self::STATUS_ORDERED and $this->status === self::STATUS_OPEN;
    }
}
