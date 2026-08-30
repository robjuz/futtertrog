<?php

namespace Database\Factories;

use App\Models\Meal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price' => $this->faker->randomNumber(3),
            'title' => $this->faker->sentence,
            'description' => $this->faker->sentences(1, true),
            'date' => today()->toDateTimeString(),
            'provider' => null,
        ];
    }

    public function inFuture(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => today()->addDays(rand(1, 10)),
            ];
        });
    }

    public function inPast(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => today()->subDay(),
            ];
        });
    }
}
