<?php

namespace Tests\Feature;

use App\Deposit;
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

        $this->actingAs($user)->get(route('home'))->assertSee(money(10));
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

        $response = $this->login($user)->get(route('home'));
        foreach ($orderItems as $orderItem) {
            $response->assertSee($orderItem->meal->title);
            $response->assertSee(__('futtertrog.orderStatus.' . $orderItem->status));
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

        $response = $this->login($user)->get(route('home'));
        foreach ($orderItems as $orderItem) {
            $response->assertSee($orderItem->meal->title);
            $response->assertSee(__('futtertrog.orderStatus.' . $orderItem->status));
        }
    }

    /** @test */
    public function it_shows_the_users_deposit_history()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $deposit = factory(Deposit::class)->make(['status' => Deposit::STATUS_OK]);

        $user->deposits()->save($deposit);

        $this->login($user)
            ->get(route('home'))
            ->assertSee(money($deposit->value));
    }

    /** @test */
    public function it_does_not_show_processing_deposits()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $deposit = factory(Deposit::class)->make(['status' => Deposit::STATUS_PROCESSING]);

        $user->deposits()->save($deposit);

        $this->login($user)
            ->get(route('home'))
            ->assertDontSee(money($deposit->value));
    }
}
