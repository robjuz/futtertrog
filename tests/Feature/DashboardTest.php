<?php

namespace Tests\Feature;

use App\Deposit;
use App\Meal;
use App\Order;
use App\OrderItem;
use App\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    /** @test */
    public function it_shows_the_users_current_balance()
    {
        $user = User::factory()->create();

        $user->deposits()->create([
            'value' => 10
        ]);

        $this->actingAs($user)->get(route('home'))->assertSee(money(10));
    }

    /** @test */
    public function it_shows_the_users_today_orders()
    {
        $user = User::factory()->create();

        $orderItems = OrderItem::factory()->count( 10)->create([
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
        $user = User::factory()->create();

        $orderItems = OrderItem::factory()->count(5)->create([
            'user_id' => $user->id,
            'meal_id' => Meal::factory()->inFuture()
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
        $user = User::factory()->create();
        $deposit =  Deposit::factory()->make(['status' => Deposit::STATUS_OK]);

        $user->deposits()->save($deposit);

        $this->login($user)
            ->get(route('home'))
            ->assertSee(money($deposit->value));
    }

    /** @test */
    public function it_does_not_show_processing_deposits()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $deposit =  Deposit::factory()->make(['status' => Deposit::STATUS_PROCESSING]);

        $user->deposits()->save($deposit);

        $this->login($user)
            ->get(route('home'))
            ->assertDontSee(money($deposit->value));
    }
}
