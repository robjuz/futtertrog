<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * App\User.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property array|null $settings
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Deposit[] $deposits
 * @property-read mixed $balance
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable, HasPushSubscriptions;

    const SETTING_NEW_ORDER_POSSIBILITY_NOTIFICATION = 'newOrderPossibilityNotification';
    const SETTING_NO_ORDER_NOTIFICATION = 'noOrderNotification';
    const SETTING_NO_ORDER_FOR_NEXT_DAY_NOTIFICATION = 'noOrderForNextDayNotification';
    const SETTING_MEAL_PREFERENCES = 'mealPreferences';
    const SETTING_MEAL_AVERSION = 'mealAversion';
    const SETTING_HIDE_DASHBOARD_MEAL_DESCRIPTION = 'hideDashboardMealDescription';
    const SETTING_HIDE_ORDERING_MEAL_DESCRIPTION = 'hideOrderingMealDescription';
    const SETTING_DARK_MODE = 'darkMode';
    const SETTING_LANGUAGE = 'language';
    const SETTING_MEALS_LIST_TYPE = 'meals_list_type';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'settings' => 'array',
    ];

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

        static::creating(function (User $user) {
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

    /**
     * @return array
     */
    public function getCheckoutData(): array
    {
        $deposit = $this->deposits()->whereStatus(Deposit::STATUS_PROCESSING)->firstOrFail();

        return [
            'items'               => [
                [
                    'name'  => trans('Futtertrog deposit'),
                    'price' => $deposit->value,
                    'qty'   => 1,
                ],
            ],
            'invoice_description' => null,
            'invoice_id'          => null,
            'return_url'          => route('paypal.express_checkout_success'),
            'cancel_url'          => url('/'),
            'total'               => $deposit->value,
        ];
    }

    public function gravatarUrl($size = 100)
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?s='.$size;
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
}
