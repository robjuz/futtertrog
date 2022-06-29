<?php

namespace Database\Factories;

use App\Meal;
use App\Order;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'meal_id' => Meal::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function inFuture()
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_id' => Meal::factory()->inFuture(),
            ];
        });
    }

    public function inPast()
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_id' => Meal::factory()->inPast(),
            ];
        });
    }

}
