<?php

namespace Database\Factories;

use App\Models\CredentialChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CredentialChallenge> */
class CredentialChallengeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->legacy(),
            'purpose' => CredentialChallenge::LEGACY_PASSWORD,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subSecond()]);
    }

    public function consumed(): static
    {
        return $this->state(fn (array $attributes) => ['consumed_at' => now()]);
    }

    public function linking(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => CredentialChallenge::LINK_IDENTITY,
            'pending_provider' => 'gitlab',
            'pending_subject' => fake()->uuid(),
        ]);
    }
}
