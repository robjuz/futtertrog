<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;
use OpenApi\Annotations as OA;

/**
 * App\OrderItem.
 *
 * @OA\Schema (
 *      required={"title", "description", "price"},
 *      @OA\Property ( property="id", ref="#/components/schemas/id" ),
 *      @OA\Property ( property="order_id", ref="#/components/schemas/id" ),
 *      @OA\Property( property="meal", ref="#/components/schemas/Meal"),
 *      @OA\Property( property="user", ref="#/components/schemas/User"),
 *      @OA\Property( property="quantity", type="number"),
 *      @OA\Property( property="created_at",type="string", format="date-time", readOnly="true" ),
 *      @OA\Property( property="updated_at",type="string", format="date-time", readOnly="true" ),
 *  ),
 * 
 *  @OA\Schema(
 *      schema="OrderItems",
 *      type="array",
 *      @OA\Items( type="object", ref="#/components/schemas/OrderItem" )
 *  ),
 * @mixin IdeHelperOrderItem
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['user_id', 'meal_id'];

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
        return $this->meal->price->multiply($this->quantity);
    }

    public function getStatusAttribute()
    {
        return $this->order->status;
    }

    public function getDateAttribute()
    {
        return $this->meal->date;
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereRelation('meal', 'date', today());
    }

    public function scopeDate(Builder $query, $date): Builder
    {
        return $query->whereRelation('meal', 'date', $date);
    }
}
