<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Meal.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $provider
 * @property float $price
 * @property \Illuminate\Support\Carbon $date_from
 * @property \Illuminate\Support\Carbon $date_to
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $orderItems
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDateFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDateTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Meal whereProvider($value)
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
      self::PROVIDER_PARADIES_PIZZA,
    ];

    protected $guarded = [];

    protected $dates = ['date_from', 'date_to'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
