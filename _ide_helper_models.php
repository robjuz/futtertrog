<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Deposit.
 *
 * @property int $id
 * @property int $user_id
 * @property int $value
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $status
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereValue($value)
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @mixin IdeHelperDeposit
 * @method static \Database\Factories\DepositFactory factory($count = null, $state = [])
 */
	class Deposit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisabledNotification whereUserId($value)
 */
	class DisabledNotification extends \Eloquent {}
}

namespace App\Models{
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
 * @mixin IdeHelperMeal
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $external_id
 * @property string $title
 * @property string|null $description
 * @property mixed|null $provider
 * @property \Cknow\Money\Money|null $price
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $image
 * @property mixed|null $info
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $is_hated
 * @property-read mixed $is_preferred
 * @property-read mixed $variant_title
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $orderItems
 * @property-read int|null $order_items_count
 * @property-read Meal|null $parent
 * @property-read \App\MealCollection<int, Meal> $variants
 * @property-read int|null $variants_count
 * @method static \App\MealCollection<int, static> all($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal byProvider($provider = null)
 * @method static \Database\Factories\MealFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal forDate($date)
 * @method static \App\MealCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meal whereUpdatedAt($value)
 */
	class Meal extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Order.
 *
 * @property int $id
 * @property AbstractMealProvider|null $provider
 * @property string $status
 * @property string $previous_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $external_id
 * @property-read mixed $is_open
 * @property-read mixed $subtotal
 * @property-read MealCollection|Meal[] $meals
 * @property-read int|null $meals_count
 * @property-read Collection|OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @method static \App\OrderCollection|static[] all($columns = ['*'])
 * @method static \App\OrderCollection|static[] get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @mixin IdeHelperOrder
 * @property int|null $user_id
 * @property string|null $payed_at
 * @property-read bool $is_payed
 * @property-read \App\Models\User|null $payedBy
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePayedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePreviousStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Models{
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
 * @mixin IdeHelperOrderItem
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property int $meal_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $date
 * @property-read mixed $status
 * @property-read mixed $subtotal
 * @property-read \App\Models\Meal|null $meal
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem date($date)
 * @method static \Database\Factories\OrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem positive()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereMealId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUserId($value)
 */
	class OrderItem extends \Eloquent {}
}

namespace App\Models{
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
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property UserSettings $settings
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $api_token
 * @property Carbon|null $deleted_at
 * @property-read Collection|Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read Money $balance
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|OrderItem[] $orderItems
 * @property-read int|null $order_items_count
 * @property-read int|null $push_subscriptions_count
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
 * @mixin \Eloquent
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @mixin IdeHelperUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisabledNotification> $disabledNotifications
 * @property-read int|null $disabled_notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $futureOrders
 * @property-read int|null $future_orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $orderHistory
 * @property-read int|null $order_history_count
 * @property-read \App\OrderCollection<int, \App\Models\Order> $payedOrders
 * @property-read int|null $payed_orders_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 */
	class User extends \Eloquent {}
}

