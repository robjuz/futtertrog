<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    const STATUS_OPEN = 'open';
    const STATUS_ORDERED = 'ordered';

    public static $statuses = [
        self::STATUS_OPEN,
        self::STATUS_ORDERED
    ];

    protected $dates = ['date'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

//    public function meals()
//    {
//        return $this->hasManyThrough(Meal::class, OrderItem::class, 'order_id', 'id', 'id', 'meal_id');
//    }
//
//    public function users()
//    {
//        return $this->hasManyThrough(Meal::class, OrderItem::class, 'order_id', 'id', 'id', 'user_id');
//    }

}
