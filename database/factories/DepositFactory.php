<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepositFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'value' => $this->faker->randomFloat(2, -10, 10),
            'comment' => $this->faker->sentence,
            'status' => \App\Models\Deposit::STATUS_OK,
        ];
    }
}