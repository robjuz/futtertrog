<?php

use Faker\Generator as Faker;

$factory->define(App\Order::class, function (Faker $faker) {

    $meal = factory('App\Meal')->create();
    return [
        'date' => $meal->date_from->toDateString(),
        'user_id' => function(){ return factory('App\User')->create()->id;},
        'meal_id' => $meal->id,
        'quantity' => $faker->numberBetween(1,10)
    ];
});
