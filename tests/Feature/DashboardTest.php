<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    /** @test */
    public function it_shows_the_users_current_balance()
    {
        $user = factory('App\User')->create();

        $user->deposits()->create([
            'value' => 10
        ]);

        $this->actingAs($user)->get('/')->assertSee('10,00 €');
    }

    /** @test */
    public function it_shows_the_users_today_orders()
    {
        $user = factory('App\User')->create();

        $order = factory('App\Order')->create([
            'date' => today()
        ]);

        $orderItems = factory('App\OrderItem', 10)->create([
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
        $user = factory('App\User')->create();

        $order = factory('App\Order')->create([
            'date' => today()->addDays(1)
        ]);

        $orderItems = factory('App\OrderItem', 5)->create([
            'order_id' => $order->id,
            'user_id' => $user->id
        ]);

        $response = $this->login($user)->get('/');
        foreach ($orderItems as $orderItem) {
            $response->assertSee($orderItem->meal->title);
        }
    }
}
