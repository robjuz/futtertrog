<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Models\Meal;
use App\Models\Order;
use App\Models\User;
use Cknow\Money\Money;
use Tests\TestCase;

class SystemTest extends TestCase
{
    /** @test */
    public function it_knows_it_current_balance()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $deposite = Deposit::create([
            'user_id' => $user->id,
            'value' => 10
        ]);

        $this->assertEquals(Money::parse(10), app('system_balance'));

        /** @var Meal $meal */
        $meal = Meal::factory()->create(['price' => 5]);

        $orderItem = $user->order($meal);

        $this->assertEquals(Money::parse(10), app('system_balance'));

        /** @var Order $order */
        $order = $orderItem->order;

        $order->update([
            'payed_at' => now()
        ]);

        $this->assertEquals(Money::parse(5), app('system_balance'));
    }
}
