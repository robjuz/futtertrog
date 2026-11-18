<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AreaAccessPolicyTest extends TestCase
{
    #[Test]
    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function administrative_capabilities_require_an_active_admin(bool $admin, bool $deleted): void
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $recipient = User::factory()->create();
        $meal = Meal::factory()->inFuture()->create();
        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['user_id' => $recipient->id, 'meal_id' => $meal->id, 'order_id' => $order->id]);
        $deposit = Deposit::factory()->create();
        if ($deleted) {
            $user->delete();
        }
        $gate = Gate::forUser($user);
        $allowed = $admin && !$deleted;

        foreach ([['access-administration', []], ['send-notifications', []],
            ['create', Meal::class], ['update', $meal], ['delete', $meal],
            ['list', Order::class], ['view', $order], ['update', $order], ['delete', $order], ['autoOrder', $order],
            ['administerAny', OrderItem::class], ['administer', $item], ['orderFor', [OrderItem::class, $meal, $recipient]],
            ['viewAny', Deposit::class], ['create', Deposit::class], ['update', $deposit], ['delete', $deposit],
            ['transfer', [Deposit::class, $recipient, $user]],
            ['viewAny', User::class], ['view', $recipient], ['create', User::class],
            ['update', $recipient], ['delete', $recipient], ['restore', $recipient]] as [$ability, $arguments]) {
            $this->assertSame($allowed, $gate->allows($ability, $arguments), $ability.' '.(is_object($arguments) ? $arguments::class : json_encode($arguments)));
        }
        $this->assertFalse($gate->allows('unregistered-ability'));
        $this->assertFalse($gate->allows('delete', $user));
        $this->assertFalse($gate->allows('forceDelete', $user));
    }

    #[Test]
    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([false, true])]
    #[TestWith([true, true])]
    public function self_service_capabilities_require_ownership_and_deadline_for_every_role(bool $admin, bool $deleted): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_admin' => $admin]);
        $own = OrderItem::factory()->inFuture()->create(['user_id' => $user->id]);
        $expired = OrderItem::factory()->create(['user_id' => $user->id]);
        $foreign = OrderItem::factory()->inFuture()->create();
        if ($deleted) {
            $user->delete();
        }
        $gate = Gate::forUser($user);

        $this->assertSame(!$deleted, $gate->allows('list', OrderItem::class));
        $this->assertSame(!$deleted, $gate->allows('view', $own));
        $this->assertSame(!$deleted, $gate->allows('view', $expired));
        $this->assertSame(!$deleted, $gate->allows('create', [OrderItem::class, today()->addDay()]));
        $this->assertFalse($gate->allows('create', [OrderItem::class, today()]));
        $this->assertFalse($gate->allows('create', [OrderItem::class, today()->subDay()]));
        $this->assertFalse($gate->allows('view', $foreign));
        foreach (['update', 'delete'] as $ability) {
            $this->assertSame(!$deleted, $gate->allows($ability, $own));
            $this->assertFalse($gate->allows($ability, $foreign));
            $this->assertFalse($gate->allows($ability, $expired));
        }
    }
}
