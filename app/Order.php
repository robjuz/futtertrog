<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * App\Order.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $provider
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $subtotal
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @method static \App\OrderCollection|static[] all($columns = ['*'])
 * @method static \App\OrderCollection|static[] get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereUpdatedAt($value)
 * @mixin \Eloquent
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
     * @param  array $models
     * @return \App\MealCollection
     */
    public function newCollection(array $models = [])
    {
        return new OrderCollection($models);
    }

    public function canBeAutoOrderedByHolzke()
    {
        if ($this->provider !== Meal::PROVIDER_HOLZKE) {
            return false;
        }

        if ($this->status === self::STATUS_ORDERED) {
            return false;
        }

        if (now()->isAfter($this->date)) {
            return false;
        }

        return true;
    }

    public function canBeUpdatedByHolzke()
    {
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
}
