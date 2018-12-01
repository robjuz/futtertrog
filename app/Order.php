<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $guarded = [];

    protected $dates = ['date'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function meals()
    {
        return $this->belongsToMany(Meal::class)
            ->as('order_details')
            ->withPivot('quantity')
            ->wherePivot('quantity', '>', 0);
    }
}
