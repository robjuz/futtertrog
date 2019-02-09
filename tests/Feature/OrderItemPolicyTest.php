<?php

namespace Tests\Feature;

use App\OrderItem;
use Tests\TestCase;

class OrderItemPolicyTest extends TestCase
{
    /** @test */
    public function acting_as_admin()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->loginAsAdmin();

        $this->assertTrue(auth()->user()->can('list', OrderItem::class));
        $this->assertTrue(auth()->user()->can('create', OrderItem::class));

        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->can('update', $orderItem));
        $this->assertTrue(auth()->user()->can('delete', $orderItem));
    }

    /** @test */
    public function acting_as_user()
    {

        $this->login();

        $this->assertTrue(auth()->user()->can('list', OrderItem::class));
        $this->assertTrue(auth()->user()->can('create', OrderItem::class));

        $orderItem = factory(OrderItem::class);
        $this->assertTrue(auth()->user()->cannot('view', $orderItem));
        $this->assertTrue(auth()->user()->cannot('update', $orderItem));
        $this->assertTrue(auth()->user()->cannot('delete', $orderItem));

        $orderItem = factory(OrderItem::class)->make()->user()->associate(auth()->user());
        $orderItem->save();
        $this->assertTrue(auth()->user()->can('view', $orderItem));
        $this->assertTrue(auth()->user()->can('update', $orderItem));
        $this->assertTrue(auth()->user()->can('delete', $orderItem));
    }
}
