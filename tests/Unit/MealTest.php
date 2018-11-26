<?php

namespace Tests\Unit;

use App\Models\Meal;
use App\Models\User;
use App\UserSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class MealTest extends TestCase
{
    public function testOrderItems()
    {
        $meal = Meal::factory()->create();

        $this->assertInstanceOf(Collection::class, $meal->orderItems);
    }

    /** @test */
    public function variant_name_consist_of_parent_name_and_variant_name()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(Meal::factory()->make());

        $this->assertTrue(Str::containsAll($variant->title, [$meal->title, $variant->title]));
    }

    /** @test */
    public function hated_takes_precedence_before_preferred()
    {

        $settings = new UserSettings();
        $settings->mealPreferences = 'preferred';
        $settings->mealAversion = 'hated';

        /** @var User $user */
        $user = User::factory()->create([
            'settings' => $settings
        ]);

        $this->actingAs($user);

        /** @var Meal $preferredMeal */
        $preferredMeal = Meal::factory()->create([
            'title' => 'preferred'
        ]);

        $this->assertTrue($preferredMeal->is_preferred);
        $this->assertFalse($preferredMeal->is_hated);

        /** @var Meal $hatedMeal */
        $hatedMeal =  Meal::factory()->create([
            'title' => 'hated'
        ]);

        $this->assertFalse($hatedMeal->is_preferred);
        $this->assertTrue($hatedMeal->is_hated);

        /** @var Meal $meal */
        $meal =  Meal::factory()->create([
            'title' => 'hated and preferred'
        ]);

        $this->assertTrue($meal->is_hated);
        $this->assertFalse($meal->is_preferred);

    }

    /** @test */
    public function it_knows_it_is_ordered()
    {

        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $meal->order($user->id);

        $this->assertTrue($meal->isOrdered($user));
    }

    /** @test */
    public function it_knows_its_variant_is_ordered()
    {

        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->inFuture()->make()
        );

        /** @var User $user */
        $user = User::factory()->create();

        $variant->order($user->id);

        $this->assertTrue($meal->isOrdered($user));
    }
}
