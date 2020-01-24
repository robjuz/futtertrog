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

$factory->state(App\OrderItem::class, 'in_future', function () {
    $meal = factory('App\Meal')->state('in_future')->create();

    return [
        'order_id' => function () use ($meal) {
            return factory('App\Order')->create(['date' => $meal->date_from])->id;
        },
        'meal_id' => $meal->id,
    ];
});

$factory->state(App\OrderItem::class, 'in_past', function () {
    $meal = factory('App\Meal')->state('in_past')->create();

    return [
        'order_id' => function () use ($meal) {
            return factory('App\Order')->create(['date' => $meal->date_from])->id;
        },
        'meal_id' => $meal->id,
    ];
});
