<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 *
 * @OA\Schema(
 *      required={"password", "email", "name"},
 *      @OA\Property( property="id", ref="#/components/schemas/id" ),
 *      @OA\Property(property="email", type="string", format="email", description="User unique email address", example="john@example.com"),
 *      @OA\Property(property="name", type="string", example="John"),
 *      @OA\Property(property="api_token", type="string", readOnly="true"),
 *      @OA\Property( property="created_at",type="string", format="date-time" ),
 *      @OA\Property( property="updated_at",type="string", format="date-time" ),
 * )
 *
 * App\User.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property array|null $settings
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $api_token
 * @property string|null $phone_number
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read mixed $balance
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\NotificationChannels\WebPush\PushSubscription[] $pushSubscriptions
 * @property-read int|null $push_subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasPushSubscriptions;
    use SoftDeletes;

    const SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION = 'newOrderPossibilityNotification';
    const SETTING_NO_ORDER_NOTIFICATION = 'noOrderNotification';
    const SETTING_NO_ORDER_FOR_NEXT_DAY_NOTIFICATION = 'noOrderForNextDayNotification';
    const SETTING_NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION = 'noOrderForNextWeekNotification';
    const SETTING_MEAL_PREFERENCES = 'mealPreferences';
    const SETTING_MEAL_AVERSION = 'mealAversion';
    const SETTING_HIDE_DASHBOARD_MEAL_DESCRIPTION = 'hideDashboardMealDescription';
    const SETTING_HIDE_ORDERING_MEAL_DESCRIPTION = 'hideOrderingMealDescription';
    const SETTING_LANGUAGE = 'language';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'settings' => 'array',
    ];

    protected $dates = ['email_verified_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
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

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function getBalanceAttribute()
    {
        if (empty($this->attributes['balance'])) {
            $this->loadMissing('orderItems.meal');

            $deposits = $this->deposits()->whereStatus(Deposit::STATUS_OK)->sum('value');
            $orders = $this->orderItems->sum(function ($order) {
                return $order->meal->price * $order->quantity;
            });

            $this->attributes['balance'] = $deposits - $orders;
        }

        return $this->attributes['balance'];
    }

    public function markAsAdmin()
    {
        return $this->forceFill([
            'is_admin' => true,
        ])->save();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function generateApiToken()
    {
        $this->api_token = Str::random(10);

        return $this;
    }

    /**
     * Route notifications for the Nexmo channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForNexmo($notification)
    {
        return $this->phone_number;
    }
}
