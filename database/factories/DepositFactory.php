<?php

use Faker\Generator as Faker;

$factory->define(App\Deposit::class, function (Faker $faker) {
    return [
        'user_id' => factory('App\User'),
        'value' => $faker->randomFloat(2, -10, 10),
        'comment' => $faker->sentence,
        'status' => \App\Deposit::STATUS_OK
    ];
});
