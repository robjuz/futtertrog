<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Money\Money;

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
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property int $meal_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $status
 * @property-read Money $subtotal
 * @property-read \App\Meal $meal
 * @property-read \App\Order $order
 * @property-read \App\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereMealId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereUserId($value)
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
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

    public function scopeToday($query)
    {
        return $query->whereHas(
            'order',
            function ($query) {
                $query->whereDate('date', today());
            }
        );
    }
}
