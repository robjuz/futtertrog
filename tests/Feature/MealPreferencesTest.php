<?php

namespace Tests\Feature;

use App\Meal;
use App\User;
use Tests\TestCase;

class MealPreferencesTest extends TestCase
{
    /** @test */
    public function it_gives_preferred_meals_higher_priority_and_hated_meals_lover_priority()
    {
        $user = factory(User::class)->create();

        $user->settings = [
            User::SETTING_MEAL_PREFERENCES => 'pancake',
            User::SETTING_MEAL_AVERSION => 'pumpkin'
        ];
        $user->save();

        $meal1 = factory(Meal::class)->create([
            'date_from' => today(),
            'date_to' => today(),
            'description' => 'pumpkin'
        ]);
        $meal2 = factory(Meal::class)->create([
            'date_from' => today(),
            'date_to' => today(),
            'description' => 'pancake'
        ]);
        $meal3 = factory(Meal::class)->create([
            'date_from' => today(),
            'date_to' => today(),
            'description' => 'meal_3'
        ]);
        $meal4 = factory(Meal::class)->create([
            'date_from' => today(),
            'date_to' => today(),
            'description' => 'meal_4'
        ]);

        $this->login($user)
            ->getJson(route('meals.index'))
            ->assertSeeInOrder([
                $meal2->title,
                $meal3->title,
                $meal4->title,
                $meal1->title,
            ]);
    }
}
