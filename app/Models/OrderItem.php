<?php

namespace App\Models;

use Carbon\Carbon;
use Cknow\Money\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\OrderItem.
 *
 * @OA\Schema (
 *      required={"title", "description", "price"},
 *
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
 *
 *      @OA\Items( type="object", ref="#/components/schemas/OrderItem" )
 *  ),
 *
 * @property-read mixed $subtotal
 * @property-read mixed $status
 * @property-read mixed $date
 *
 * @mixin \Eloquent
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['user_id', 'meal_id'];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'meal_id' => 'integer',
        'quantity' => 'integer',
        'date' => 'datetime',
    ];

    /**
     * @return BelongsTo<Meal, $this>
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getSubtotalAttribute(): Money
    {
        return $this->meal->price->multiply($this->quantity);
    }

    public function getStatusAttribute(): string
    {
        return $this->order->status;
    }

    public function getDateAttribute(): Carbon
    {
        return $this->meal->date;
    }

    /**
     * @phpstan-param Builder<OrderItem> $query
     *
     * @return Builder<OrderItem>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereRelation('meal', 'date', today());
    }

    /**
     * @phpstan-param Builder<OrderItem> $query
     *
     * @return Builder<OrderItem>
     */
    public function scopeDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereRelation('meal', 'date', $date);
    }

    /**
     * @phpstan-param Builder<OrderItem> $query
     *
     * @return Builder<OrderItem>
     */
    public function scopePositive(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }
}
