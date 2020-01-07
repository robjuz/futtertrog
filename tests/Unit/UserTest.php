<?php

namespace Tests\Unit;

use App\Deposit;
use App\Meal;
use App\Order;
use App\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UserTest extends TestCase
{


    /** @test */
    public function it_has_many_deposits()
    {
        /** @var \App\User $user */
        $user = factory(User::class)->create();

        $this->assertInstanceOf(Collection::class, $user->deposits);
    }

    /** @test */
    public function it_has_many_order_items()
    {
        /** @var \App\User $user */
        $user = factory(User::class)->create();

        $this->assertInstanceOf(Collection::class, $user->orderItems);
    }

    /** @test */
    public function it_knows_its_current_balance()
    {
        /** @var \App\User $user */
        $user = factory(User::class)->create();

        $user->deposits()->saveMany([
            factory(Deposit::class)->make(['value' => 1000]),
            factory(Deposit::class)->make(['value' => 1500]),
        ]);

        $meal = factory(Meal::class)->create([
            'price' => 5.45
        ]);

        /** @var \App\Order $order */
        $order = factory(Order::class)->create([
            'date' => $meal->date_from
        ]);

        $order->orderItems()->create([
            'user_id' => $user->id,
            'meal_id' => $meal->id,
            'quantity' => 2
        ]);

        $this->assertEquals(1410, $user->balance);
    }

    /** @test */
    public function it_can_be_marked_as_admin()
    {
        $user = factory(User::class)->create(['is_admin' => false]);

        $user->markAsAdmin();

        $this->assertTrue($user->fresh()->is_admin);
    }

}
