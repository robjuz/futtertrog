<?php

use Faker\Generator as Faker;

$factory->define(App\OrderItem::class, function (Faker $faker) {
    $meal = factory('App\Meal')->create();

    return [
        'order_id' => function () {
            return factory('App\Order')->create()->id;
        },
        'user_id' => function () {
            return factory('App\User')->create()->id;
        },
        'meal_id' => $meal->id,
        'quantity' => $faker->numberBetween(1, 10),
    ];
});
