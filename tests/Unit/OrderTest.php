<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{


    /** @test */
    public function it_belongs_to_an_user()
    {
        $order = factory('App\Order')->create();

        $this->assertInstanceOf('App\User', $order->user);
    }

    /** @test */
    public function it_belongs_to_a_meal()
    {
        $order = factory('App\Order')->create();

        $this->assertInstanceOf('App\Meal', $order->meal);
    }
}
