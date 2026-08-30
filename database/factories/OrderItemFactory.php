<?php

namespace Database\Factories;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'meal_id' => Meal::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function inFuture(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_id' => Meal::factory()->inFuture(),
            ];
        });
    }

    public function inPast(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'meal_id' => Meal::factory()->inPast(),
            ];
        });
    }
}
