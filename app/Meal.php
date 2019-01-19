<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Meal
 *
 * @property int $id
 * @property int|null $order_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $title
 * @property string $description
 * @property float $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Meal extends Model
{
    const PROVIDER_HOLZKE = 'Holzke';
    const PROVIDER_PARADIES_PIZZA = 'Paradies Pizza';

    public static $providers = [
      self::PROVIDER_HOLZKE,
      self::PROVIDER_PARADIES_PIZZA
    ];

    protected $guarded = [];

    protected $dates = ['date', 'orderable_until'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('quantity');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class);
    }

    public function setOrderableUntilAttribute($value)
    {
        if ($value) {
            $this->attributes['orderable_until'] = Carbon::createFromFormat('Y-m-d\TH:i', $value);
        }
    }
}
