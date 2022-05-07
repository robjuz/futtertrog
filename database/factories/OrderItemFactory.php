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
        $meal = Meal::factory()->create();

        return [
            'order_id' => function () use ($meal) {
                return Order::factory()->create(['date' => $meal->date_from])->id;
            },
            'user_id' => User::factory(),
            'meal_id' => $meal->id,
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function inFuture()
    {

        return $this->state(function (array $attributes) {

            $meal = Meal::factory()->inFuture()->create();
            return [
                'order_id' => function () use ($meal) {
                    return Order::factory()->create(['date' => $meal->date_from])->id;
                },
                'meal_id' => $meal->id,
            ];
        });
    }

    public function inPast()
    {

        return $this->state(function (array $attributes) {

            $meal = Meal::factory()->inPast()->create();
            return [
                'order_id' => function () use ($meal) {
                    return Order::factory()->create(['date' => $meal->date_from])->id;
                },
                'meal_id' => $meal->id,
            ];
        });
    }

}
