<?php

use Faker\Generator as Faker;

$factory->define(App\Deposit::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory('App\User')->create()->id;
        },
        'value' => $faker->randomFloat(2, -10, 10),
        'comment' => $faker->sentence,
    ];
});
