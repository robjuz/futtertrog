<?php

namespace App\Models;

use App\UserSettings;
use Cknow\Money\Money;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @OA\Schema (
 *      required={"password", "email", "name"},
 *
 *      @OA\Property( property="id", ref="#/components/schemas/id" ),
 *      @OA\Property(property="email", type="string", format="email", description="User unique email address", example="john@example.com"),
 *      @OA\Property(property="name", type="string", example="John"),
 *      @OA\Property(property="api_token", type="string", readOnly="true"),
 *      @OA\Property( property="created_at",type="string", format="date-time" ),
 *      @OA\Property( property="updated_at",type="string", format="date-time" ),
 * )
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property UserSettings<array-key, mixed> $settings
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $api_token
 * @property Carbon|null $deleted_at
 * @property-read Collection|Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read Money $balance
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification>|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @property-read int|null $push_subscriptions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 *
 * @mixin \Eloquent
 *
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'settings' => UserSettings::class,
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $user) {
            $user->generateApiToken();
        });
    }

    /** @return HasMany<Deposit, $this> */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class)->latest();
    }

    public function getBalanceAttribute(): Money
    {
        if (empty($this->attributes['balance'])) {
            $this->loadMissing('orderItems.meal');

            $deposits = Money::parse($this->deposits()->whereStatus(Deposit::STATUS_OK)->sum('value'));
            $orders = Money::sum(
                Money::parse(0),
                ...$this->orderItems->map(
                    fn (OrderItem $orderItem) => $orderItem->meal->price->multiply($orderItem->quantity)
                )
            );

            $payedOrders = Money::sum(
                Money::parse(0),
                ...$this->payedOrders->map->subtotal
            );

            $this->attributes['balance'] = $deposits->subtract($orders)->add($payedOrders);
        }

        return $this->attributes['balance'];
    }

    public function markAsAdmin(): bool
    {
        return $this->forceFill([
            'is_admin' => true,
        ])->save();
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<Order, $this> */
    public function payedOrders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function generateApiToken(): static
    {
        $this->api_token = Str::random(10);

        return $this;
    }

    /** @return HasMany<OrderItem, $this> */
    public function futureOrders(): HasMany
    {
        return $this->orderItems()
            ->positive()
            ->with(['meal'])
            ->whereRelation('meal', 'date', '>', today());
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderHistory(): HasMany
    {
        return $this->orderItems()
            ->positive()
            ->with(['order', 'meal'])
            ->latest();
    }

    /** @return HasMany<DisabledNotification, $this> */
    public function disabledNotifications(): HasMany
    {
        return $this->hasMany(DisabledNotification::class);
    }

    public function order(Meal $meal): OrderItem
    {
        return $meal->order($this);
    }
}
