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

    /** @test */
    public function it_knows_its_subtotal()
    {
        $meal = factory('App\Meal')->create(['price' => 1]);

        /** @var \App\OrderItem $orderItem */
        $orderItem = factory('App\OrderItem')->make(['quantity' => 2]);
        $orderItem->meal()->associate($meal)->save();

        $this->assertEquals(2, $orderItem->subtotal);
    }
}
