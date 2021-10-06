<?php

namespace Tests\Unit;

use App\Meal;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class MealTest extends TestCase
{
    public function testOrderItems()
    {
        $meal = factory(Meal::class)->create();

        $this->assertInstanceOf(Collection::class, $meal->orderItems);
    }

    /** @test */
    public function variant_name_consist_of_parent_name_and_variant_name()
    {
        /** @var Meal $meal */
        $meal = factory(Meal::class)->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(factory(Meal::class)->make());

        $this->assertTrue(Str::containsAll($variant->title, [$meal->title, $variant->title]));
    }

    /** @test */
    public function hated_takes_precedence_before_preferred()
    {
        /** @var User $user */
        $user = factory(User::class)->create([
            'settings' => [
                User::SETTING_MEAL_PREFERENCES => 'preferred',
                User::SETTING_MEAL_AVERSION => 'hated'
            ]
        ]);

        $this->actingAs($user);

        /** @var Meal $preferredMeal */
        $preferredMeal = factory(Meal::class)->create([
            'title' => 'preferred'
        ]);

        $this->assertTrue($preferredMeal->is_preferred);
        $this->assertFalse($preferredMeal->is_hated);

        /** @var Meal $hatedMeal */
        $hatedMeal =  factory(Meal::class)->create([
            'title' => 'hated'
        ]);

        $this->assertFalse($hatedMeal->is_preferred);
        $this->assertTrue($hatedMeal->is_hated);

        /** @var Meal $meal */
        $meal =  factory(Meal::class)->create([
            'title' => 'hated and preferred'
        ]);

        $this->assertTrue($meal->is_hated);
        $this->assertFalse($meal->is_preferred);

    }
}
