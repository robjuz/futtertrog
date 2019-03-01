<?php

namespace Tests\Feature;

use App\OrderItem;
use App\User;
use Tests\TestCase;

class IcalTest extends TestCase
{
    /** @test */
    public function it_allows_to_export_users_order_history_to_ical()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->create(['user_id' => $user->id]);

        $this->login($user)
            ->get(route('meals.ical'))
            ->assertSee($orderItem->meal->title.' ('.$orderItem->quantity.')')
            ->assertSee($orderItem->meal->description);
    }
}
