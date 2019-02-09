<?php

namespace Tests\Feature;

use App\Order;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    /** @test */
    public function acting_as_admin()
    {
        $order = factory(Order::class)->create();

        $this->loginAsAdmin();

        $this->assertTrue(auth()->user()->can('list', Order::class));
        $this->assertTrue(auth()->user()->can('create', Order::class));

        $this->assertTrue(auth()->user()->can('view', $order));
        $this->assertTrue(auth()->user()->can('update', $order));
        $this->assertTrue(auth()->user()->can('delete', $order));
    }

    /** @test */
    public function acting_as_user()
    {

        $this->login();

        $order = factory(Order::class);

        $this->assertTrue(auth()->user()->cannot('list', Order::class));
        $this->assertTrue(auth()->user()->cannot('create', Order::class));
        $this->assertTrue(auth()->user()->cannot('view', $order));
        $this->assertTrue(auth()->user()->cannot('update', $order));
        $this->assertTrue(auth()->user()->cannot('delete', $order));
    }
}
