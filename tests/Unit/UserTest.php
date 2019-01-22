<?php

namespace Tests\Unit;

use App\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_many_deposits()
    {
        /** @var \App\User $user */
        $user = factory('App\User')->create();

        $this->assertInstanceOf('Illuminate\Support\Collection', $user->deposits);
    }

    /** @test */
    public function it_has_many_orders()
    {
        /** @var \App\User $user */
        $user = factory('App\User')->create();

        $this->assertInstanceOf('Illuminate\Support\Collection', $user->orders);
    }

    /** @test */
    public function it_has_many_meal()
    {
        /** @var \App\User $user */
        $user = factory('App\User')->create();

        $this->assertInstanceOf('Illuminate\Support\Collection', $user->meals);
    }

    /** @test */
    public function it_knows_its_current_balance()
    {
        /** @var \App\User $user */
        $user = factory('App\User')->create();

        $user->deposits()->saveMany([
            factory('App\Deposit')->make(['value' => 10]),
            factory('App\Deposit')->make(['value' => 15]),
        ]);

        $meal = factory('App\Meal')->create([
            'price' => 5.45
        ]);

        Order::create([
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id,
            'quantity' => 2
        ]);

        $this->assertEquals(14.10, $user->balance);
    }


}