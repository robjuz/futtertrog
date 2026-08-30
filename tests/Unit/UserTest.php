<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Models\Meal;
use App\Models\OrderItem;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{


    #[Test]
    public function it_has_many_deposits()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $this->assertInstanceOf(Collection::class, $user->deposits);
    }

    #[Test]
    public function it_has_many_order_items()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $this->assertInstanceOf(Collection::class, $user->orderItems);
    }

    #[Test]
    public function it_knows_its_current_balance()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $user->deposits()->saveMany([
             Deposit::factory()->make(['value' => 1000]),
             Deposit::factory()->make(['value' => 1500]),
        ]);

        $meal = Meal::factory()->create([
            'price' => 5.45
        ]);

        OrderItem::factory()->create([
            'user_id' => $user->id,
            'meal_id' => $meal->id,
            'quantity' => 2
        ]);

        $this->assertEquals(Money::parse(1410), $user->balance);
    }

    #[Test]
    public function it_can_be_marked_as_admin()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $user->markAsAdmin();

        $this->assertTrue($user->fresh()->is_admin);
    }

    #[Test]
    public function admin_cannot_delete_his_own_account()
    {
        /** @var User $user */
        $user = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($user->cannot('delete', $user));
    }
}
