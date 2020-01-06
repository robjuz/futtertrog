<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * App\Meal.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $provider
 * @property integer $price
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
    const PROVIDER_CALL_A_PIZZA = 'Call A Pizza';
    const PROVIDER_TEST_SUPLIER = 'Test Suplier';

    public static $providers = [
        self::PROVIDER_HOLZKE,
        self::PROVIDER_CALL_A_PIZZA,
        self::PROVIDER_TEST_SUPLIER,
    ];

    protected $guarded = [];

    protected $dates = ['date_from', 'date_to'];

    /**
     * @param array $columns
     * @return \App\MealCollection
     */
    public static function get($columns = [])
    {
        return parent::get($columns);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Set the price in cent
     *
     * @param  integer  $value
     * @return void
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = intval(100 * $value);
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

    public function getTitleClasses()
    {
        $classes = [];
        if ($this->is_hated or $this->is_preferred) {
            $classes[] = 'font-weight-bold';
        }

        if ($this->is_hated) {
            $classes[] = 'text-danger';
        } elseif ($this->is_preferred) {
            $classes[] = 'text-success';
        }

        return implode(' ', $classes);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array $models
     * @return \App\MealCollection
     */
    public function newCollection(array $models = [])
    {
        return new MealCollection($models);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date_from', '<=', $date)->whereDate('date_to', '>=', $date);
    }
}
