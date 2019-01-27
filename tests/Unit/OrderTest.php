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
}
