<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\ExternalIdentity> */
class ExternalIdentityFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory()->disabled(), 'provider' => 'gitlab', 'subject' => fake()->unique()->uuid()];
    }
}
