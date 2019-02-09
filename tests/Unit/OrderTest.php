<?php

namespace Tests\Unit;

use Tests\TestCase;

class OrderTest extends TestCase
{
    /** @test */
    public function it_has_many_order_items()
    {
        /** @var \App\Order $order */
        $order = factory('App\Order')->create();

        $this->assertInstanceOf('\Illuminate\Support\Collection', $order->orderItems);
    }

    /** @test */
    public function it_knows_its_subtotal()
    {
        $meal1 = factory('App\Meal')->create(['price' => 1]);
        $meal2 = factory('App\Meal')->create(['price' => 2]);
        $order = factory('App\Order')->create();

        /** @var \App\OrderItem $orderItem1 */
        $orderItem1 = factory('App\OrderItem')->make(['quantity' => 2]);
        $orderItem1->meal()->associate($meal1);
        $orderItem1->order()->associate($order);
        $orderItem1->save();

        /** @var \App\OrderItem $orderItem2 */
        $orderItem2 = factory('App\OrderItem')->make(['quantity' => 2]);
        $orderItem2->meal()->associate($meal2);
        $orderItem2->order()->associate($order);
        $orderItem2->save();


        $this->assertEquals(6, $order->subtotal);
    }
}
