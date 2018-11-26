<?php

use Faker\Generator as Faker;

$factory->define(App\Meal::class, function (Faker $faker) {
    return [
        'date' => $faker->dateTimeThisMonth,
        'price' => $faker->randomNumber(2),
        'title' => $faker->sentence,
        'description' => $faker->sentences(3, true)
    ];
});
