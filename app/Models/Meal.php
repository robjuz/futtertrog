<?php

namespace App\Models;

use App\Casts\MealInfoCast;
use App\Casts\MealProviderCast;
use App\MealCollection;
use Cknow\Money\Casts\MoneyIntegerCast;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Schema (
 *      required={"title", "description", "price", "date"},
 *
 *      @OA\Property( property="id", ref="#/components/schemas/id" ),
 *      @OA\Property( property="date", type="date" ),
 *      @OA\Property( property="title", type="string"),
 *      @OA\Property( property="variant_title", type="string"),
 *      @OA\Property( property="description", type="string"),
 *      @OA\Property( property="price", type="number", format="float"),
 *      @OA\Property( property="created_at",type="string", format="date-time", readOnly="true" ),
 *      @OA\Property( property="updated_at",type="string", format="date-time", readOnly="true" ),
 *      @OA\Property(
 *          property="variants",
 *          type="array",
 *          nullable=true,
 *
 *          @OA\Items( type="object", ref="#/components/schemas/Meal" )
 *      ),
 *
 *      @OA\Property( property="parent", type="object", ref="#/components/schemas/Meal", nullable=true ),
 *  ),
 *
 * @property-read string $title
 * @property-read string $variant_title
 * @property-read bool $is_preferred
 * @property-read bool $is_hated
 *
 * @mixin \Eloquent
 */
class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use HasFactory;

    protected $guarded = ['variants'];

    protected $appends = ['variant_title'];

    protected $casts = [
        'info' => MealInfoCast::class,
        'price' => MoneyIntegerCast::class,
        'provider' => MealProviderCast::class,
        'date' => 'datetime',
    ];

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Meal, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(Meal::class, 'parent_id');
    }

    /** @return BelongsTo<Meal, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'parent_id');
    }

    public function getTitleAttribute(?string $value): string
    {
        if ($this->parent) {
            return implode(' ', [$this->parent->title, $value]);
        }

        return $value ?? '';
    }

    public function getVariantTitleAttribute(): string
    {
        return $this->attributes['title'];
    }

    public function getIsPreferredAttribute(): bool
    {
        if (! isset($this->attributes['is_preferred'])) {
            $this->attributes['is_preferred'] = false;

            if (Auth::check() and ! $this->is_hated) {
                $preferences = Auth::user()->settings->mealPreferences;
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

    public function getIsHatedAttribute(): bool
    {
        if (! isset($this->attributes['is_hated'])) {
            $this->attributes['is_hated'] = false;

            if (Auth::check()) {
                $aversions = Auth::user()->settings->mealAversion;
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
     */
    public function newCollection(array $models = []): \Illuminate\Database\Eloquent\Collection
    {
        return new MealCollection(
            collect($models)->map(function ($model) {
                assert($model instanceof self);

                return $model;
            })->values()
        );
    }

    /**
     * @phpstan-param Builder<Meal> $query
     *
     * @return Builder<Meal>
     */
    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * @phpstan-param Builder<Meal> $query
     *
     * @return Builder<Meal>
     */
    public function scopeByProvider(Builder $query, ?string $provider = null): Builder
    {
        if (! $provider) {
            return $query;
        }

        return $query->where('provider', $provider);
    }

    public function order(User|int $user, int $quantity = 1): OrderItem
    {
        $userId = $user->id ?? $user;

        return DB::transaction(function () use ($userId, $quantity) {
            if ($this->provider) {
                $order = $this->provider->getOrder($this->date);
            } else {
                $order = Order::query()
                    ->whereHas('meals', fn (Builder $query) => $query->whereDate('date', $this->date))
                    ->firstOrCreate(['provider' => null], ['status' => Order::STATUS_OPEN]);
            }

            /** @var OrderItem $orderItem */
            $orderItem = $order->orderItems()
                ->updateOrCreate(
                    [
                        'meal_id' => $this->id,
                        'user_id' => $userId,
                    ],
                    [
                        'quantity' => $quantity,
                    ]
                );

            $order->reopen();

            return $orderItem;
        });
    }

    public function isOrdered(?User $user = null): bool
    {
        return (bool) $this->orderItem($user);
    }

    public function orderItem(?User $user = null): ?OrderItem
    {
        return OrderItem::query()
            ->whereIn('meal_id', $this->variants()->pluck('id')->push($this->id))
            ->where('user_id', $user ? $user->id : auth()->id())
            ->where('quantity', '>', 0)
            ->first();
    }
}
