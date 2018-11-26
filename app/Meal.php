<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    protected $fillable = ['title', 'description', 'date', 'price'];

    protected $dates = ['date'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function order()
    {
        return $this->belongsToMany(Order::class)
            ->as('order_details')
            ->withPivot(['quantity']);
    }
}
