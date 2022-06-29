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
        $orderItem = OrderItem::factory()->create();

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
        $orderItem = OrderItem::factory();
        $this->assertTrue(auth()->user()->cannot('view', $orderItem));
        $this->assertTrue(auth()->user()->cannot('update', $orderItem));
        $this->assertTrue(auth()->user()->cannot('delete', $orderItem));

        //own order in future
        $futureMeal = Meal::factory()->inFuture()->create();

        $orderItem = OrderItem::factory()->create([
            'meal_id' => $futureMeal->id,
            'user_id' => auth()->id(),
        ]);
        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->can('update', $orderItem));
        $this->assertTrue(auth()->user()->can('delete', $orderItem));

        //own order in future
        $pastMeal = Meal::factory()->inPast()->create();

        $orderItem = OrderItem::factory()->create([
            'meal_id' => $pastMeal->id,
            'user_id' => auth()->id(),
        ]);
        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->cannot('update', $orderItem));
        $this->assertTrue(auth()->user()->cannot('delete', $orderItem));
    }
}
