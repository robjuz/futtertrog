<?php

namespace App;

use Cknow\Money\Casts\MoneyDecimalCast;
use Cknow\Money\Casts\MoneyIntegerCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema (
 *      required={"title", "description", "price"},
 *      @OA\Property( property="id", ref="#/components/schemas/id" ),
 *      @OA\Property( property="title", type="string", readOnly="true"),
 *      @OA\Property( property="variant_title", type="string", readOnly="true"),
 *      @OA\Property( property="description", type="string", readOnly="true"),
 *      @OA\Property( property="price", type="number", format="float"),
 *      @OA\Property( property="created_at",type="string", format="date-time", readOnly="true" ),
 *      @OA\Property( property="updated_at",type="string", format="date-time", readOnly="true" ),
 *      @OA\Property( property="variants",type="array", @OA\Items( type="object", ref="#/components/schemas/Meal" ), nullable=true ),
 *      @OA\Property( property="parent", type="object", ref="#/components/schemas/Meal", nullable=true ),
 *  ),
 *  @OA\Schema(
 *      schema="Meals",
 *      type="array",
 *      @OA\Items( type="object", ref="#/components/schemas/Meal" )
 *  ),
 * @mixin IdeHelperMeal
 */
class Meal extends Model
{
    use HasFactory;

    protected $guarded = ['variants'];

    protected $dates = ['date_from', 'date_to'];

    protected $appends = ['variant_title'];

    protected $casts = [
        'info' => MealInfo::class,
        'price' => MoneyIntegerCast::class,
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Meal::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'parent_id');
    }

    public function getTitleAttribute($value)
    {
        if ($this->parent) {
            return implode(' ', [$this->parent->title, $value]);
        }

        return $value;
    }

    public function getVariantTitleAttribute()
    {
        return $this->attributes['title'];
    }

    public function getIsPreferredAttribute()
    {
        if (! isset($this->attributes['is_preferred'])) {
            $this->attributes['is_preferred'] = false;

            if (Auth::check() and !$this->is_hated) {
                $preferences = Auth::user()->settings[User::SETTING_MEAL_PREFERENCES] ?? '';
                $preferences = array_map('trim', explode(',', $preferences));

                foreach ($preferences as $preference) {
                    if (Str::contains(strtolower($this->title), strtolower($preference))) {
                        $this->attributes['is_preferred'] = true;
                        break;
                    }
                    if (Str::contains(strtolower($this->description), strtolower($preference))) {
                        $this->attributes['is_preferred'] = true;
                        break;
                    }
                }
            }
        }

        return $this->attributes['is_preferred'];
    }

    public function getIsHatedAttribute()
    {
        if (! isset($this->attributes['is_hated'])) {
            $this->attributes['is_hated'] = false;

            if (Auth::check()) {
                $aversions = Auth::user()->settings[User::SETTING_MEAL_AVERSION] ?? '';
                $aversions = array_map('trim', explode(',', $aversions));

                foreach ($aversions as $aversion) {
                    if (Str::contains(strtolower($this->title), strtolower($aversion))) {
                        $this->attributes['is_hated'] = true;
                        break;
                    }
                    if (Str::contains(strtolower($this->description), strtolower($aversion))) {
                        $this->attributes['is_hated'] = true;
                        break;
                    }
                }
            }
        }

        return $this->attributes['is_hated'];
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \App\MealCollection
     */
    public function newCollection(array $models = []): MealCollection
    {
        return new MealCollection($models);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date_from', '<=', $date)->whereDate('date_to', '>=', $date);
    }

    public function scopeByProvider($query, $provider = null)
    {
        if (! $provider) {
            return $query;
        }

        return $query->where('provider', $provider);
    }

    public function order(int $userId, $date, $quantity = 1): OrderItem
    {
        /** @var Order $order */
        $order = Order::query()
            ->updateOrCreate(
                [
                    'date' => $date,
                    'provider' => $this->provider,
                ],
                [
                    'status' => Order::STATUS_OPEN,
                ]
            );

        /** @var OrderItem $orderItem */
        $orderItem = $order->orderItems()
            ->create(
                [
                    'meal_id' => $this->id,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                ]
            );

        return $orderItem;
    }
}
