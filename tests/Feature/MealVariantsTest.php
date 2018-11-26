<?php

namespace Tests\Feature;

use App\Models\Meal;
use Tests\TestCase;

class MealVariantsTest extends TestCase
{
    /** @test */
    public function a_meal_can_have_variants()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var Meal $variant */
        $variant = Meal::factory()->make();

        $this->assertCount(0, $meal->fresh()->variants);

        $meal->variants()->save($variant);

        $this->assertCount(1, $meal->fresh()->variants);
    }

    /** @test */
    public function a_variant_knows_his_parent()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var Meal $variant */
        $variant = Meal::factory()->make();

        $meal->variants()->save($variant);

        $this->assertTrue($meal->is($variant->parent));
    }
}
