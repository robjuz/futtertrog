<?php

use Faker\Generator as Faker;

$factory->define(App\Order::class, function (Faker $faker) {
    return [
        'date' => $faker->dateTimeThisMonth,
        'provider' => null,
    ];
});
