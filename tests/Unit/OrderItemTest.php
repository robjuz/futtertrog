<?php

namespace Tests\Unit;

use App\Meal;
use App\Order;
use App\OrderItem;
use App\User;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    public function testUser()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->assertInstanceOf(User::class, $orderItem->user);
    }

    public function testMeal()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->assertInstanceOf(Meal::class, $orderItem->meal);
    }

    public function testOrder()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->assertInstanceOf(Order::class, $orderItem->order);
    }

    public function testGetSubtotalAttribute()
    {
        $meal = factory(Meal::class)->create(['price' => 1]);

        /** @var \App\OrderItem $orderItem */
        $orderItem = factory(OrderItem::class)->make(['quantity' => 2]);
        $orderItem->meal()->associate($meal)->save();

        $this->assertEquals(2, $orderItem->subtotal);
    }
}
