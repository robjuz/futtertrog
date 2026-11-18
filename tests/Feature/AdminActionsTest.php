<?php

namespace Tests\Feature;

use App\Actions\Admin\{AutoOrder, Bookings, ImportMeals, OrderItems, Orders, SendNotification, Users};
use App\Events\OrderUpdated;
use App\MealProviders\Holzke;
use App\Models\AdminActivity;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Deposit;
use App\Providers\MealProvidersServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    #[Test]
    public function replaying_a_booking_key_does_not_book_twice(): void
    {
        $user = User::factory()->create();
        $this->loginAsAdmin();
        $data = ['user_id' => $user->id, 'value' => 1234, 'comment' => 'Booking'];

        $this->withHeader('Idempotency-Key', 'booking-1')->postJson(route('deposits.store'), $data)->assertRedirect();
        $this->postJson(route('deposits.store'), $data)->assertRedirect();

        $this->assertDatabaseCount('deposits', 1);
        $this->assertSame(1234, Deposit::sole()->value);
    }

    #[Test]
    public function a_successful_booking_records_actor_target_and_only_allowed_fields(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->login($admin);
        $this->freezeTime();

        $this->postJson(route('deposits.store'), [
            'user_id' => $user->id, 'value' => 1234, 'password' => 'secret', 'api_token' => 'secret',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_activities', [
            'actor_id' => $admin->id, 'action' => 'deposit.created',
            'target_type' => Deposit::class, 'target_id' => Deposit::sole()->id, 'state' => 'succeeded',
        ]);
        $activity = AdminActivity::sole();
        $this->assertSame(now()->timestamp, $activity->created_at->timestamp);
        $this->assertSame(['before' => [], 'after' => ['user_id' => $user->id, 'value' => 1234]], $activity->changes);
        $this->assertStringNotContainsString('secret', $activity->toJson());
    }

    #[Test]
    public function legacy_booking_retries_are_deduplicated_but_a_new_key_allows_a_deliberate_second_booking(): void
    {
        $user = User::factory()->create();
        $this->loginAsAdmin();
        $data = ['user_id' => $user->id, 'value' => 1050];

        $this->postJson(route('deposits.store'), $data)->assertRedirect();
        $this->postJson(route('deposits.store'), $data)->assertRedirect();
        $this->assertDatabaseCount('deposits', 1);
        $this->withHeader('Idempotency-Key', 'new-booking')->postJson(route('deposits.store'), $data)->assertRedirect();

        $this->assertDatabaseCount('deposits', 2);
        $this->assertDatabaseCount('admin_activities', 2);
    }

    #[Test]
    public function reusing_a_booking_key_for_changed_data_returns_409(): void
    {
        $user = User::factory()->create();
        $this->loginAsAdmin()->withExceptionHandling();
        $this->withHeader('Idempotency-Key', 'booking')->postJson(route('deposits.store'), ['user_id' => $user->id, 'value' => 10])->assertRedirect();

        $this->postJson(route('deposits.store'), ['user_id' => $user->id, 'value' => 20])->assertConflict();

        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('admin_activities', 1);
    }

    #[Test]
    public function transfers_replay_both_legs_without_creating_more_money(): void
    {
        $actor = User::factory()->admin()->create();
        $source = User::factory()->create();
        $target = User::factory()->create();
        $data = ['source' => $source->id, 'target' => $target->id, 'value' => 10.5];
        $this->login($actor);
        $this->withHeader('Idempotency-Key', 'transfer')->postJson(route('deposits.transfer'), $data)->assertRedirect();

        $legs = app(Bookings::class)->transfer($actor, $data, 'transfer');

        $this->assertDatabaseCount('deposits', 2);
        $this->assertSame([-1050, 1050], array_map(fn (Deposit $deposit): int => $deposit->value, $legs));
        $this->assertSame('0', $source->fresh()->balance->add($target->fresh()->balance)->getAmount());
        $this->assertSame('deposit.transferred', AdminActivity::sole()->action);
    }

    #[Test]
    public function controller_and_direct_booking_actions_preserve_values_updates_and_deletion(): void
    {
        $actor = User::factory()->admin()->create();
        $user = User::factory()->create();
        $data = ['user_id' => $user->id, 'value' => -10.5, 'comment' => 'Adjustment'];
        $this->login($actor);
        $this->withHeader('Idempotency-Key', 'controller')->postJson(route('deposits.store'), $data)->assertRedirect();
        $controllerDeposit = Deposit::sole();

        $directDeposit = app(Bookings::class)->create($actor, $data, 'direct');
        $this->assertSame(-1050, $directDeposit->value);
        $this->assertSame($controllerDeposit->value, $directDeposit->value);
        $data['value'] = 2250;
        $this->putJson(route('deposits.update', $controllerDeposit), $data)->assertRedirect();
        $directDeposit = app(Bookings::class)->update($actor, $directDeposit, $data);
        $this->assertSame(2250, $directDeposit->value);
        $this->assertSame($controllerDeposit->fresh()->value, $directDeposit->value);
        $this->deleteJson(route('deposits.destroy', $controllerDeposit))->assertRedirect();
        app(Bookings::class)->delete($actor, $directDeposit);

        $this->assertDatabaseCount('deposits', 0);
        $this->assertSame(['deposit.created', 'deposit.created', 'deposit.updated', 'deposit.updated', 'deposit.deleted', 'deposit.deleted'], AdminActivity::orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function controller_and_direct_order_actions_preserve_quantities_totals_status_and_events(): void
    {
        $this->freezeTime();
        $actor = User::factory()->admin()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $meal = Meal::factory()->inPast()->create(['price' => 1234]);
        Event::fake([OrderUpdated::class]);
        $this->login($actor);
        $this->postJson(route('admin.order_items.store'), ['meal_id' => $meal->id, 'user_id' => $first->id, 'quantity' => 2])->assertCreated();
        $controllerItem = OrderItem::sole();

        $directItem = app(OrderItems::class)->create($actor, ['meal_id' => $meal->id, 'user_id' => $second->id, 'quantity' => 2]);
        $this->assertSame('2468', $directItem->subtotal->getAmount());
        $this->assertEquals($controllerItem->subtotal, $directItem->subtotal);
        $this->putJson(route('admin.order_items.update', $controllerItem), ['quantity' => 3])->assertOk();
        $directItem = app(OrderItems::class)->update($actor, $directItem, ['quantity' => 3]);
        $this->assertSame(3, $controllerItem->fresh()->quantity);
        $this->assertSame(3, $directItem->quantity);
        $this->assertEquals($controllerItem->fresh()->subtotal, $directItem->subtotal);
        $order = $directItem->order;
        $this->putJson(route('orders.update', $order), ['status' => Order::STATUS_ORDERED, 'user_id' => $first->id])->assertOk();
        $order = app(Orders::class)->update($actor, $order, ['status' => Order::STATUS_ORDERED, 'user_id' => $first->id]);
        $this->assertSame(Order::STATUS_ORDERED, $order->status);
        $this->assertSame($first->id, $order->user_id);
        $this->deleteJson(route('admin.order_items.destroy', $controllerItem))->assertNoContent();
        app(OrderItems::class)->delete($actor, $directItem);

        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        Event::assertDispatchedTimes(OrderUpdated::class, 6);
        $this->assertSame(8, AdminActivity::count());
    }

    #[Test]
    public function a_failed_audit_rolls_back_the_financial_change_and_creates_no_entry(): void
    {
        $actor = User::factory()->admin()->create();
        $user = User::factory()->create();
        AdminActivity::creating(function (): void { throw new RuntimeException('audit storage failed'); });

        try {
            app(Bookings::class)->create($actor, ['user_id' => $user->id, 'value' => 1234], 'rollback');
            $this->fail('The audit failure must abort the booking.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit storage failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('deposits', 0);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    public function a_failed_transfer_rolls_back_both_legs_and_the_audit(): void
    {
        $actor = User::factory()->admin()->create();
        $source = User::factory()->create();
        $target = User::factory()->create();
        Deposit::creating(function (Deposit $deposit): void {
            if ($deposit->value > 0) {
                throw new RuntimeException('second leg failed');
            }
        });

        try {
            app(Bookings::class)->transfer($actor, ['source' => $source->id, 'target' => $target->id, 'value' => 100]);
            $this->fail('The second leg must fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('second leg failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('deposits', 0);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    /** @return iterable<string, array{string, string}> */
    public static function unauthorizedActions(): iterable
    {
        foreach (['booking', 'booking-update', 'booking-delete', 'transfer', 'item', 'item-update', 'item-delete', 'order', 'order-delete', 'auto', 'role', 'deactivate', 'restore', 'import', 'notification'] as $action) {
            foreach (['user', 'disabled-admin', 'demoted-admin'] as $identity) {
                yield $action.' '.$identity => [$action, $identity];
            }
        }
    }

    #[Test]
    #[DataProvider('unauthorizedActions')]
    public function direct_actions_authorize_the_current_actor_before_any_effect(string $action, string $identity): void
    {
        Http::preventStrayRequests();
        Notification::fake();
        $actor = User::factory()->create(['is_admin' => $identity !== 'user']);
        if ($identity === 'disabled-admin') {
            User::whereKey($actor->id)->delete();
        } elseif ($identity === 'demoted-admin') {
            User::whereKey($actor->id)->update(['is_admin' => false]);
        }
        $target = User::factory()->create();
        $order = Order::factory()->create();
        $deposit = in_array($action, ['booking-update', 'booking-delete'], true) ? Deposit::factory()->create(['value' => 100]) : null;
        $item = in_array($action, ['item-update', 'item-delete'], true) ? OrderItem::factory()->create(['quantity' => 1]) : null;

        try {
            match ($action) {
                'booking' => app(Bookings::class)->create($actor, ['user_id' => $target->id, 'value' => 100]),
                'booking-update' => app(Bookings::class)->update($actor, $deposit, ['user_id' => $target->id, 'value' => 999]),
                'booking-delete' => app(Bookings::class)->delete($actor, $deposit),
                'item-update' => app(OrderItems::class)->update($actor, $item, ['quantity' => 9]),
                'item-delete' => app(OrderItems::class)->delete($actor, $item),
                'order-delete' => app(Orders::class)->delete($actor, $order),
                'transfer' => app(Bookings::class)->transfer($actor, ['source' => $target->id, 'target' => $target->id, 'value' => 100]),
                'item' => app(OrderItems::class)->create($actor, []),
                'order' => app(Orders::class)->update($actor, $order, ['status' => Order::STATUS_ORDERED]),
                'auto' => app(AutoOrder::class)->execute($actor, $order),
                'role' => app(Users::class)->changeRole($actor, $target, true),
                'deactivate' => app(Users::class)->deactivate($actor, $target),
                'restore' => app(Users::class)->restore($actor, $target),
                'import' => app(ImportMeals::class)->execute($actor, 'Holzke', '2026-11-18'),
                'notification' => app(SendNotification::class)->execute($actor, ['user_id' => $target->id, 'subject' => 'Subject', 'body' => 'Body']),
            };
            $this->fail('Unauthorized action must fail.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('deposits', $deposit ? 1 : 0);
            if ($deposit) {
                $this->assertSame(100, $deposit->fresh()->value);
            }
            if ($item) {
                $this->assertSame(1, $item->fresh()->quantity);
            }
            $this->assertDatabaseCount('admin_activities', 0);
            $this->assertFalse($target->fresh()->is_admin);
            $this->assertNotSoftDeleted($target);
            $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
            Notification::assertNothingSent();
            Http::assertNothingSent();
        }
    }

    #[Test]
    public function direct_actions_validate_and_drop_fields_outside_their_contract(): void
    {
        $actor = User::factory()->admin()->create();
        $user = User::factory()->create();
        try {
            app(Bookings::class)->create($actor, ['user_id' => $user->id, 'value' => 'invalid']);
            $this->fail('Invalid money must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('value', $exception->errors());
        }
        $deposit = app(Bookings::class)->create($actor, ['user_id' => $user->id, 'value' => 100, 'status' => 'processing', 'password' => 'secret']);

        $this->assertSame(Deposit::STATUS_OK, $deposit->fresh()->status);
        $this->assertStringNotContainsString('password', AdminActivity::sole()->toJson());
    }

    #[Test]
    public function direct_role_and_lifecycle_actions_preserve_last_admin_and_self_deletion_protection(): void
    {
        $admin = User::factory()->admin()->create();
        $disabled = User::factory()->admin()->create();
        $disabled->delete();
        foreach (['demote', 'delete'] as $operation) {
            try {
                $operation === 'demote'
                    ? app(Users::class)->changeRole($admin, $admin, false)
                    : app(Users::class)->deactivate($admin, $admin);
                $this->fail('The last admin must remain active.');
            } catch (AuthorizationException) {
                $this->assertTrue($admin->fresh()->is_admin);
                $this->assertNotSoftDeleted($admin);
                $this->assertDatabaseCount('admin_activities', 0);
            }
        }
        $restored = app(Users::class)->restore($admin, $disabled);
        $this->assertNotSoftDeleted($restored);
        app(Users::class)->changeRole($admin, $restored, false);
        app(Users::class)->deactivate($admin, $restored);

        $this->assertSame(['user.restored', 'user.updated', 'user.deactivated'], AdminActivity::orderBy('id')->pluck('action')->all());
        $this->assertSoftDeleted($restored);
        $this->assertStringNotContainsString('password', AdminActivity::all()->toJson());
    }

    #[Test]
    public function notification_actions_match_the_controller_without_logging_message_content(): void
    {
        Notification::fake();
        $actor = User::factory()->admin()->create();
        $recipient = User::factory()->create();
        $data = ['user_id' => $recipient->id, 'subject' => 'Private subject', 'body' => 'Private body'];
        $this->login($actor)->post(route('notification.store'), $data)->assertRedirect();

        app(SendNotification::class)->execute($actor, $data);

        Notification::assertSentToTimes($recipient, \App\Notifications\CustomNotification::class, 2);
        $this->assertDatabaseCount('admin_activities', 2);
        $this->assertSame(['recipient_ids' => [$recipient->id]], AdminActivity::first()->changes['after']);
        $this->assertStringNotContainsString('Private', AdminActivity::all()->toJson());
    }

    #[Test]
    public function imports_match_the_controller_and_only_record_allowed_summary_fields(): void
    {
        Http::preventStrayRequests();
        config(['services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);
        $actor = User::factory()->admin()->create();
        $provider = $this->partialMock(Holzke::class);
        $provider->shouldReceive('createMealsDataForDate')->twice()->withArgs(fn ($date): bool => $date->toDateString() === '2026-11-18')->andReturn(3);
        $provider->shouldReceive('notifyAboutNewOrderPossibilities')->twice();
        $this->login($actor)->post(route('meals.import'), ['date' => '2026-11-18', 'provider' => 'Holzke', 'notify' => true])->assertRedirect();

        $this->assertSame(3, app(ImportMeals::class)->execute($actor, 'Holzke', '2026-11-18', true));

        $this->assertSame(['provider' => 'Holzke', 'date' => '2026-11-18', 'count' => 3], AdminActivity::first()->changes['after']);
        $this->assertDatabaseCount('admin_activities', 2);
        Http::assertNothingSent();
    }

    #[Test]
    public function creating_an_administrator_logs_the_initial_permission_without_credentials(): void
    {
        $actor = User::factory()->admin()->create();
        $this->login($actor);

        $this->postJson(route('users.store'), ['name' => 'New admin', 'email' => 'new-admin@example.com',
            'is_admin' => true, 'password' => 'private-password', 'password_confirmation' => 'private-password'])->assertOk();

        $activity = AdminActivity::sole();
        $this->assertSame('user.created', $activity->action);
        $this->assertSame($actor->id, $activity->actor_id);
        $this->assertSame(['is_admin' => true], $activity->changes['after']);
        $this->assertStringNotContainsString('private-password', $activity->toJson());
    }
    #[Test]
    public function upserting_an_existing_order_item_records_the_previous_quantity_as_an_update(): void
    {
        $actor = User::factory()->admin()->create();
        $meal = Meal::factory()->inFuture()->create();
        $item = app(OrderItems::class)->create($actor, ['meal_id' => $meal->id, 'quantity' => 1]);

        app(OrderItems::class)->create($actor, ['meal_id' => $meal->id, 'quantity' => 3]);

        $this->assertDatabaseCount('order_items', 1);
        $activity = AdminActivity::latest('id')->firstOrFail();
        $this->assertSame('order_item.updated', $activity->action);
        $this->assertSame(1, $activity->changes['before']['quantity']);
        $this->assertSame(3, $activity->changes['after']['quantity']);
        $this->assertSame((string) $item->id, $activity->target_id);
    }
}

