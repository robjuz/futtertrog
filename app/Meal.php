<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 *  @OA\Schema(
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
 *
 *  @OA\Schema(
 *      schema="Meals",
 *      type="array",
 *      @OA\Items( type="object", ref="#/components/schemas/Meal" )
 *  ),
 *
 * App\Meal.
 *
 * @property int $id
 * @property string $title
 * @property string $variant_title
 * @property string|null $description
 * @property string|null $provider
 * @property int $price
 * @property \Illuminate\Support\Carbon $date_from
 * @property \Illuminate\Support\Carbon $date_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $image
 * @property string|null $external_id
 * @property-read mixed $is_hated
 * @property-read mixed $is_preferred
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Meal[] $variants
 * @property-read \App\Meal $parent
 * @property-read int|null $order_items_count
 * @method static \App\MealCollection|static[] all($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal forDate($date)
 * @method static \App\MealCollection|static[] get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDateFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDateTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Meal extends Model
{
    const PROVIDER_HOLZKE = 'Holzke';
    const PROVIDER_CALL_A_PIZZA = 'Call A Pizza';

    public static $providers = [
        self::PROVIDER_HOLZKE,
        self::PROVIDER_CALL_A_PIZZA,
    ];

    protected $guarded = [];

    protected $dates = ['date_from', 'date_to'];

    protected $appends = ['variant_title'];

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

    /**
     * Set the price in cent.
     *
     * @param  int  $value
     * @return void
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = is_float($value) ? intval(100 * $value) : $value;
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

            if (Auth::check()) {
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
     * @param  array $models
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

    public function order($userId, $date, $quantity = 1): OrderItem
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
