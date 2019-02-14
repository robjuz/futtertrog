<?php

namespace Tests\Feature;

use App\Order;
use App\OrderItem;
use App\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /** @test */
    public function it_shows_the_users_current_balance()
    {
        $user = factory(User::class)->create();

        $user->deposits()->create([
            'value' => 10
        ]);

        $this->actingAs($user)->get('/')->assertSee('10,00 €');
    }

    /** @test */
    public function it_shows_the_users_today_orders()
    {
        $user = factory(User::class)->create();

        $order = factory(Order::class)->create([
            'date' => today()
        ]);

        $orderItems = factory(OrderItem::class, 10)->create([
            'order_id' => $order->id,
            'user_id' => $user->id
        ]);

        $response = $this->login($user)->get('/');
        foreach ($orderItems as $orderItem) {
            $response->assertSee($orderItem->meal->title);
        }

    }

    /** @test */
    public function it_shows_the_users_upcoming_orders()
    {
        $user = factory(User::class)->create();

        $order = factory(Order::class)->create([
            'date' => today()->addDays(1)
        ]);

        $orderItems = factory(OrderItem::class, 5)->create([
            'order_id' => $order->id,
            'user_id' => $user->id
        ]);

        $response = $this->login($user)->get('/');
        foreach ($orderItems as $orderItem) {
            $response->assertSee($orderItem->meal->title);
        }
    }
}
