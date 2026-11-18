<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\AdminActivity> */
class AdminActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => 'deposit.created',
            'target_type' => Deposit::class,
            'target_id' => Deposit::factory(),
            'state' => 'succeeded',
            'changes' => ['before' => [], 'after' => ['value' => 100]],
        ];
    }
}
