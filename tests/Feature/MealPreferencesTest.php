<?php

namespace Tests\Feature;

use App\Meal;
use App\User;
use App\UserSettings;
use Tests\TestCase;

class MealPreferencesTest extends TestCase
{
    /** @test */
    public function it_gives_preferred_meals_higher_priority_and_hated_meals_lover_priority()
    {
        $user = User::factory()->create();

        $settings = new UserSettings();
        $settings->mealPreferences = 'pancake';
        $settings->mealAversion = 'pumpkin';

        $user->settings = $settings;
        $user->save();

        $meal1 = Meal::factory()->create([
            'description' => 'pumpkin'
        ]);
        $meal2 = Meal::factory()->create([
            'description' => 'pancake'
        ]);
        $meal3 = Meal::factory()->create([
            'description' => 'meal_3'
        ]);
        $meal4 = Meal::factory()->create([
            'description' => 'meal_4'
        ]);
        $meal5 = Meal::factory()->create([
            'title' => 'pumpkin'
        ]);
        $meal6 = Meal::factory()->create([
            'title' => 'pancake'
        ]);

        $this->login($user)
            ->getJson(route('meals.index'))
            ->assertSeeInOrder([
                $meal2->title,
                $meal6->title,
                $meal3->title,
                $meal4->title,
                $meal1->title,
                $meal5->title,
            ]);
    }
}
