<?php

namespace Tests\Unit;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function testUser()
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(User::class, $orderItem->user);
    }

    public function testMeal()
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Meal::class, $orderItem->meal);
    }

    public function testOrder()
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Order::class, $orderItem->order);
    }

    public function testGetSubtotalAttribute()
    {
        $meal = Meal::factory()->create(['price' => 1]);

        /** @var OrderItem $orderItem */
        $orderItem = OrderItem::factory()->make(['quantity' => 2]);
        $orderItem->meal()->associate($meal)->save();

        $this->assertEquals(Money::parse(2), $orderItem->subtotal);
    }

    public function testGetStatusAttribute() {

        /** @var OrderItem $orderItem */
        $orderItem = OrderItem::factory()->create();

        $this->assertNotNull($orderItem->status);
    }
}
