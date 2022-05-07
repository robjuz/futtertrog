<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = \Illuminate\Support\Carbon::parse($this->faker->dateTimeThisMonth)->startOfDay()->format('Y-m-d H:i:s');

        return [
            'price' => $this->faker->randomNumber('3'),
            'title' => $this->faker->sentence,
            'description' => $this->faker->sentences(1, true),
            'date_from' => $date,
            'date_to' => $date,
            'provider' => array_rand(app('mealProviders')),
        ];
    }

    public function inFuture()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_from' => today()->addDay(),
                'date_to' => today()->addDay(),
            ];
        });
    }

    public function inPast()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_from' => today(),
                'date_to' => today(),
            ];
        });
    }
}
