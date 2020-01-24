<?php

namespace Tests\Unit;

use App\Meal;
use App\Order;
use App\OrderItem;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OrderItemPolicyTest extends TestCase
{
    /** @test */
    public function acting_as_admin()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->loginAsAdmin();

        $this->assertTrue(auth()->user()->can('list', OrderItem::class));
        $this->assertTrue(auth()->user()->can('create', [OrderItem::class, today()->addDay()]));
        $this->assertTrue(auth()->user()->can('create', [OrderItem::class, today()]));
        $this->assertTrue(auth()->user()->can('create', [OrderItem::class, today()->subDay()]));

        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->can('update', $orderItem));
        $this->assertTrue(auth()->user()->can('delete', $orderItem));
    }

    /** @test */
    public function acting_as_user()
    {

        $this->login();

        $this->assertTrue(auth()->user()->can('list', OrderItem::class));
        $this->assertTrue(auth()->user()->can('create', [OrderItem::class, today()->addDay()]));
        $this->assertTrue(auth()->user()->cannot('create', [OrderItem::class, today()]));
        $this->assertTrue(auth()->user()->cannot('create', [OrderItem::class, today()->subDay()]));

        //someone elses order
        $orderItem = factory(OrderItem::class);
        $this->assertTrue(auth()->user()->cannot('view', $orderItem));
        $this->assertTrue(auth()->user()->cannot('update', $orderItem));
        $this->assertTrue(auth()->user()->cannot('delete', $orderItem));

        //own order in future
        $futureMeal = factory(Meal::class)->state('in_future')->create();
        $futureOrder = factory(Order::class)->create([
            'date' => $futureMeal->date_from
        ]);

        $orderItem = factory(OrderItem::class)->create([
            'meal_id' => $futureMeal->id,
            'user_id' => auth()->id(),
            'order_id' => $futureOrder->id
        ]);
        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->can('update', $orderItem));
        $this->assertTrue(auth()->user()->can('delete', $orderItem));

        //own order in future
        $pastMeal = factory(Meal::class)->state('in_past')->create();
        $pastOrder = factory(Order::class)->create([
            'date' => $pastMeal->date_from
        ]);

        $orderItem = factory(OrderItem::class)->create([
            'meal_id' => $pastMeal->id,
            'user_id' => auth()->id(),
            'order_id' => $pastOrder->id
        ]);
        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->cannot('update', $orderItem));
        $this->assertTrue(auth()->user()->cannot('delete', $orderItem));
    }
}
