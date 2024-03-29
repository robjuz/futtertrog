<?php

use Faker\Generator as Faker;

$factory->define(App\Meal::class, function (Faker $faker) {
    $date = \Illuminate\Support\Carbon::parse($faker->dateTimeThisMonth)->startOfDay()->format('Y-m-d H:i:s');

    return [
        'price' => $faker->randomNumber('3'),
        'title' => $faker->sentence,
        'description' => $faker->sentences(1, true),
        'date_from' => $date,
        'date_to' => $date,
        'provider' => array_rand(app('mealProviders')),
    ];
});

$factory->state(App\Meal::class, 'in_future', [
    'date_from' => today()->addDay(),
    'date_to' => today()->addDay(),
]);

$factory->state(App\Meal::class, 'in_past', [
    'date_from' => today(),
    'date_to' => today(),
]);
