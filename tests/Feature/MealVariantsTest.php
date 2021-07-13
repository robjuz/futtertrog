<?php

namespace Tests\Feature;

use App\Meal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MealVariantsTest extends TestCase
{
    /** @test */
    public function a_meal_can_have_variants()
    {
        /** @var Meal $meal */
        $meal = factory(Meal::class)->create();

        /** @var Meal $variant */
        $variant = factory(Meal::class)->make();

        $this->assertCount(0, $meal->fresh()->variants);

        $meal->variants()->save($variant);

        $this->assertCount(1, $meal->fresh()->variants);
    }

    /** @test */
    public function a_variant_knows_his_parent()
    {
        /** @var Meal $meal */
        $meal = factory(Meal::class)->create();

        /** @var Meal $variant */
        $variant = factory(Meal::class)->make();

        $meal->variants()->save($variant);

        $this->assertTrue($meal->is($variant->parent));
    }
}
