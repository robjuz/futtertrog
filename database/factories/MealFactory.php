<?php

use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

$factory->define(App\Meal::class, function (Faker $faker) {
    $date = $faker->dateTimeThisMonth->format('Y-m-d');
    return [
        'price' => $faker->randomNumber(2),
        'title' => $faker->sentence,
        'description' => $faker->sentences(3, true),
        'date_from' => $date,
        'date_to' => $date,
    ];
});
