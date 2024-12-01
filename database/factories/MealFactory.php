<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'price' => $this->faker->randomNumber('3'),
            'title' => $this->faker->sentence,
            'description' => $this->faker->sentences(1, true),
            'date' => today()->toDateTimeString(),
            'provider' => null,
        ];
    }

    public function inFuture()
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => today()->addDays(rand(1,10)),
            ];
        });
    }

    public function inPast()
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => today()->subDay(),
            ];
        });
    }
}
