<?php

namespace Tests\Unit;

use App\Meal;
use App\Order;
use App\OrderItem;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /** @test */
    public function it_has_many_order_items()
    {
        /** @var \App\Order $order */
        $order = factory(Order::class)->create();

        $this->assertInstanceOf(Collection::class, $order->orderItems);
    }

    /** @test */
    public function it_knows_its_subtotal()
    {
        $meal1 = factory(Meal::class)->create(['price' => 1]);
        $meal2 = factory(Meal::class)->create(['price' => 2]);
        $order = factory(Order::class)->create();

        /** @var \App\OrderItem $orderItem1 */
        $orderItem1 = factory(OrderItem::class)->make(['quantity' => 2]);
        $orderItem1->meal()->associate($meal1);
        $orderItem1->order()->associate($order);
        $orderItem1->save();

        /** @var \App\OrderItem $orderItem2 */
        $orderItem2 = factory(OrderItem::class)->make(['quantity' => 2]);
        $orderItem2->meal()->associate($meal2);
        $orderItem2->order()->associate($order);
        $orderItem2->save();


        $this->assertEquals(6, $order->subtotal);
    }
}
