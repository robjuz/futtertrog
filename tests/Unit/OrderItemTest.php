<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderItemTest extends TestCase
{


    /** @test */
    public function it_belongs_to_an_user()
    {
        $orderItem = factory('App\OrderItem')->create();

        $this->assertInstanceOf('App\User', $orderItem->user);
    }

    /** @test */
    public function it_belongs_to_a_meal()
    {
        $orderItem = factory('App\OrderItem')->create();

        $this->assertInstanceOf('App\Meal', $orderItem->meal);
    }
    
    /** @test */
    public function it_belongs_to_an_order()
    {
        $orderItem = factory('App\OrderItem')->create();

        $this->assertInstanceOf('App\Order', $orderItem->order);
    }
}
