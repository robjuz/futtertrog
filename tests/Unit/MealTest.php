<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MealTest extends TestCase
{

    /** @test */
    public function it_has_many_orders()
    {
        $meal = factory('App\Meal')->create();

        $this->assertInstanceOf('Illuminate\Support\Collection', $meal->orders);
    }

    /** @test */
    public function it_determines_if_it_was_ordered()
    {
        /** @var \App\User $user */
        $user = factory('App\User')->create();

        /** @var \App\Meal $meal */
        $meal = factory('App\Meal')->create();

        $user->orders()->create([
            'date' => today(),
            'meal_id' => $meal->id
        ]);

        $this->assertTrue($meal->wasOrdered(today(), $user));
    }
}
