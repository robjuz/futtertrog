<?php

use Faker\Generator as Faker;

$factory->define(
    App\OrderItem::class,
    function (Faker $faker) {
        $meal = factory('App\Meal')->create();

        return [
            'order_id' => function () use ($meal) {
                return factory('App\Order')->create(['date' => $meal->date_from])->id;
            },
            'user_id' => factory('App\User'),
            'meal_id' => $meal->id,
            'quantity' => $faker->numberBetween(1, 10),
        ];
    }
);
