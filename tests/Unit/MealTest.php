<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MealTest extends TestCase
{

    /** @test */
    public function it_has_many_order_items()
    {
        $meal = factory('App\Meal')->create();

        $this->assertInstanceOf('Illuminate\Support\Collection', $meal->orderItems);
    }
}
