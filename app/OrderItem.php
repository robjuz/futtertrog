<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Order.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Meal[] $meals
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $order_id
 * @property int $user_id
 * @property int $meal_id
 * @property int $quantity
 * @property-read \App\Meal $meal
 * @property-read \App\Order $order
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMealId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereUserId($value)
 */
class OrderItem extends Model
{
    protected $guarded = [];

    protected $dates = ['date'];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'meal_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->meal->price * $this->quantity;
    }
}
