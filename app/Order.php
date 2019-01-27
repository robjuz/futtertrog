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

}
