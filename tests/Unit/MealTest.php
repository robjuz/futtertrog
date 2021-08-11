<?php

namespace Tests\Unit;

use App\Meal;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MealTest extends TestCase
{
    public function testOrderItems()
    {
        $meal = factory(Meal::class)->create();

        $this->assertInstanceOf(Collection::class, $meal->orderItems);
    }

    /** @test */
    public function it_knows_if_it_is_ordered() {

    }
}
