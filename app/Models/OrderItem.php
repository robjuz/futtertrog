<?php

namespace App\Models;

use OpenApi\Attributes as OA;

use App\IdeHelperOrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderItem.
 *
 * @mixin IdeHelperOrderItem
 */
#[OA\Schema(
    required: ["title", "description", "price"],
    properties: [new OA\Property(
        property: "id",
        ref: "#/components/schemas/id"
    ), new OA\Property(
        property: "order_id",
        ref: "#/components/schemas/id"
    ), new OA\Property(
        property: "meal",
        ref: "#/components/schemas/Meal"
    ), new OA\Property(
        property: "user",
        ref: "#/components/schemas/User"
    ), new OA\Property(
        property: "quantity",
        type: "number"
    ), new OA\Property(
        property: "created_at",
        type: "string",
        format: "date-time",
        readOnly: true
    ), new OA\Property(
        property: "updated_at",
        type: "string",
        format: "date-time",
        readOnly: true
    )]
)]
#[OA\Schema(
    schema: "OrderItems",
    type: "array",
    items: new OA\Items(
        type: "object",
        ref: "#/components/schemas/OrderItem"
    )
)]
class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['user_id', 'meal_id'];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'meal_id' => 'integer',
        'quantity' => 'integer',
        'date' => 'datetime'
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

    public function scopePositive(Builder $query)
    {
        return $query->where('quantity','>', 0);
    }
}
