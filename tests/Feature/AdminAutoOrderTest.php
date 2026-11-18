<?php

namespace Tests\Feature;

use App\Actions\Admin\AutoOrder;
use App\Actions\Admin\OrderItems;
use App\MealProviders\Holzke;
use App\Models\AdminActivity;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Providers\MealProvidersServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class AdminAutoOrderTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        $this->createFixtureDatabase('auto_order_fixture', ['auto_order_observer']);
        $this->artisan('migrate:fresh', ['--database' => 'auto_order_fixture', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        config(['database.default' => 'auto_order_fixture']);
    }

    protected function tearDown(): void
    {
        config(['database.default' => $this->originalConnection]);
        parent::tearDown();
    }

    #[Test]
    public function autoorder_uses_the_shared_action_and_a_successful_order_cannot_be_sent_twice(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target): void { $target->markOrdered(); });
        $this->login($actor);
        app(AutoOrder::class)->execute($actor, $order);
        $this->assertSame(Order::STATUS_ORDERED, $order->fresh()->status);
        $this->assertRejected(400, fn () => app(AutoOrder::class)->execute($actor, $order));

        $this->assertSame('succeeded', AdminActivity::sole()->state);
        $this->assertSame(['status' => 'ordered'], AdminActivity::sole()->changes['after']);
        Http::assertNothingSent();
    }

    #[Test]
    #[TestWith(['running'])]
    #[TestWith(['uncertain'])]
    public function pending_or_uncertain_autoorders_refuse_repetition_with_409(string $state): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        AdminActivity::factory()->create(['actor_id' => $actor->id, 'action' => 'order.auto_ordered',
            'target_type' => Order::class, 'target_id' => $order->id, 'state' => $state]);
        $this->login($actor)->withExceptionHandling();

        $this->assertRejected(409, fn () => app(AutoOrder::class)->execute($actor, $order));

        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 1);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_provider_failure_rolls_back_local_success_and_persists_uncertainty_without_secrets(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target): void {
            $target->markOrdered();
            throw new RuntimeException('private-provider-token');
        });

        try {
            app(AutoOrder::class)->execute($actor, $order);
            $this->fail('An unclear provider result must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('private-provider-token', $exception->getMessage());
        }

        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        $this->assertSame('uncertain', AdminActivity::sole()->state);
        $this->assertSame([], AdminActivity::sole()->changes['after']);
        $this->assertStringNotContainsString('private-provider-token', AdminActivity::sole()->toJson());
        $this->assertRejected(409, fn () => app(AutoOrder::class)->execute($actor, $order));
        Http::assertNothingSent();
    }

    #[Test]
    public function a_reopened_order_uses_the_existing_provider_update_transition(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $order->update(['external_id' => 'existing-provider-order']);
        $provider->shouldNotReceive('placeOrder');
        $provider->shouldReceive('updateOrder')->once()->andReturnUsing(function (Order $target): void { $target->markOrdered(); });

        $result = app(AutoOrder::class)->execute($actor, $order);

        $this->assertSame(Order::STATUS_ORDERED, $result->status);
        $this->assertSame('existing-provider-order', $result->external_id);
        $this->assertSame('succeeded', AdminActivity::sole()->state);
        Http::assertNothingSent();
    }

    #[Test]
    public function an_enclosing_transaction_is_rejected_before_contacting_the_provider(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');

        try {
            DB::transaction(fn () => app(AutoOrder::class)->execute($actor, $order));
            $this->fail('An enclosing transaction could erase the durable reservation.');
        } catch (\LogicException $exception) {
            $this->assertSame('Autoorder requires a committed reservation; do not wrap it in a transaction.', $exception->getMessage());
        }

        $this->assertDatabaseCount('admin_activities', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function the_reservation_is_committed_before_provider_contact_and_also_blocks_scheduled_orders(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $calls = 0;
        $competingAttemptsRejected = false;
        $this->assertNotSame(DB::connection()->getPdo(), DB::connection('auto_order_observer')->getPdo());
        \Illuminate\Support\Facades\Event::listen('eloquent.created: '.AdminActivity::class, function (AdminActivity $attempt) use ($actor, $order, &$calls, &$competingAttemptsRejected): void {
            if ($attempt->action !== 'order.auto_ordered') {
                return;
            }
            DB::afterCommit(function () use ($actor, $order, &$calls, &$competingAttemptsRejected): void {
                $this->assertSame(0, DB::transactionLevel());
                $this->assertSame(0, $calls);
                $this->onConnection('auto_order_observer', function () use ($actor, $order): void {
                    $this->assertSame('running', AdminActivity::sole()->state);
                    $otherOrder = Order::findOrFail($order->id);
                    $otherActor = User::findOrFail($actor->id);
                    $this->assertRejected(409, fn () => app(AutoOrder::class)->execute($otherActor, $otherOrder));
                    $this->assertRejected(409, fn () => $otherOrder->autoOrder());
                    $this->assertDatabaseCount('admin_activities', 1);
                });
                $this->assertSame(0, $calls);
                $competingAttemptsRejected = true;
            });
        });
        $provider->shouldNotReceive('updateOrder');
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target) use ($order, &$calls, &$competingAttemptsRejected): void {
            $this->assertTrue($competingAttemptsRejected);
            if (++$calls > 1) {
                throw new RuntimeException('Duplicate provider invocation.');
            }
            $this->assertSame('running', DB::connection('auto_order_observer')->table('admin_activities')->value('state'));
            try {
                $order->fresh()->autoOrder();
                $this->fail('The scheduled entry must respect an admin reservation.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
            $target->markOrdered();
        });

        app(AutoOrder::class)->execute($actor, $order);

        $this->assertSame(1, $calls);
        $this->assertSame('succeeded', DB::connection('auto_order_observer')->table('admin_activities')->value('state'));
        Http::assertNothingSent();
    }

    #[Test]
    public function a_provider_return_without_confirmed_status_remains_uncertain_and_returns_502(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldReceive('placeOrder')->once()->andReturnNull();
        $this->login($actor)->withExceptionHandling();

        $this->assertRejected(502, fn () => app(AutoOrder::class)->execute($actor, $order));

        $this->assertSame('uncertain', AdminActivity::sole()->state);
        $this->assertSame([], AdminActivity::sole()->changes['after']);
        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function scheduled_orders_use_the_same_reservation_and_record_the_system_context(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target): void { $target->markOrdered(); });

        $order->autoOrder();

        $this->assertSame(Order::STATUS_ORDERED, $order->fresh()->status);
        $this->assertSame('succeeded', AdminActivity::sole()->state);
        $this->assertNull(AdminActivity::sole()->actor_id);
        $this->assertRejected(400, fn () => app(AutoOrder::class)->execute($actor, $order));
        $this->assertDatabaseCount('admin_activities', 1);
        Http::assertNothingSent();
    }

    #[Test]
    public function confirming_an_older_content_version_keeps_the_order_open_and_allows_a_new_attempt(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldReceive('placeOrder')->once()->andThrow(new RuntimeException('Provider result unknown.'));
        try {
            $order->autoOrder();
            $this->fail('An unclear provider result must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Provider result unknown.', $exception->getMessage());
        }
        $attempt = AdminActivity::where('action', 'order.auto_ordered')->sole();
        $this->assertSame('uncertain', $attempt->state);
        $this->assertSame($order->contentFingerprint(), $attempt->changes['before']['content_fingerprint']);
        $item = $order->orderItems()->sole();
        app(OrderItems::class)->update($actor, $item, ['quantity' => $item->quantity + 1]);

        app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'confirmed');

        $this->assertSame('succeeded', $attempt->fresh()->state);
        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        $this->assertTrue(AdminActivity::where('action', 'order.auto_order_reconciled')->sole()->changes['content_changed']);
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(fn (Order $target) => $target->markOrdered());
        $order->autoOrder();
        $this->assertSame(Order::STATUS_ORDERED, $order->fresh()->status);
        $this->assertSame(['succeeded', 'succeeded'], AdminActivity::where('action', 'order.auto_ordered')->orderBy('id')->pluck('state')->all());
        Http::assertNothingSent();
    }

    #[Test]
    public function confirming_a_legacy_attempt_without_a_content_fingerprint_keeps_the_order_open(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'uncertain',
            'changes' => ['before' => ['status' => 'open'], 'after' => []]]);

        app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'confirmed');

        $this->assertSame('succeeded', $attempt->fresh()->state);
        $this->assertSame(Order::STATUS_OPEN, $order->fresh()->status);
        $this->assertTrue(AdminActivity::where('action', 'order.auto_order_reconciled')->sole()->changes['content_changed']);
        Http::assertNothingSent();
    }

    #[Test]
    #[TestWith(['confirmed', 'succeeded', 'ordered'])]
    #[TestWith(['not_received', 'failed', 'open'])]
    public function reconciliation_records_the_actor_and_original_attempt_and_controls_retry(string $outcome, string $state, string $status): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $attempt = AdminActivity::factory()->create(['actor_id' => null, 'action' => 'order.auto_ordered',
            'target_type' => Order::class, 'target_id' => $order->id, 'state' => 'uncertain',
            'changes' => ['before' => ['status' => 'open', 'content_fingerprint' => $order->contentFingerprint()], 'after' => []]]);
        $before = $attempt->getAttributes();
        $this->assertRejected(409, fn () => $order->autoOrder());

        app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, $outcome);

        $this->assertSame($state, $attempt->fresh()->state);
        $this->assertSame($before['actor_id'], $attempt->fresh()->actor_id);
        $this->assertSame($before['changes'], $attempt->fresh()->getRawOriginal('changes'));
        $this->assertSame($status, $order->fresh()->status);
        $resolution = AdminActivity::where('action', 'order.auto_order_reconciled')->sole();
        $this->assertSame($actor->id, $resolution->actor_id);
        $this->assertSame((string) $order->id, $resolution->target_id);
        $this->assertSame($attempt->id, $resolution->changes['attempt_id']);
        $this->assertSame($outcome, $resolution->changes['outcome']);
        if ($outcome === 'confirmed') {
            $this->assertFalse($resolution->changes['content_changed']);
        } else {
            $this->assertArrayNotHasKey('content_changed', $resolution->changes);
        }
        $this->assertSame('uncertain', $resolution->changes['before']['state']);
        $this->assertSame($state, $resolution->changes['after']['state']);
        $this->assertRejected(409, fn () => app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, $outcome));
        if ($outcome === 'not_received') {
            $provider->shouldReceive('placeOrder')->once()->andReturnUsing(fn (Order $target) => $target->markOrdered());
            app(AutoOrder::class)->execute($actor, $order);
        } else {
            $provider->shouldNotReceive('placeOrder', 'updateOrder');
        }
        $this->assertRejected(400, fn () => app(AutoOrder::class)->execute($actor, $order));
        $order->fresh()->autoOrder();
        Http::assertNothingSent();
    }

    #[Test]
    public function a_running_attempt_requires_thirty_minutes_and_explicit_reconciliation(): void
    {
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        $this->freezeTime();
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'running']);
        $this->travel(29)->minutes();
        $this->assertRejected(409, fn () => app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'not_received'));
        $this->travel(1)->minutes();
        $this->assertRejected(409, fn () => $order->autoOrder());
        app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'not_received');
        $this->assertSame('failed', $attempt->fresh()->state);
        $this->assertDatabaseCount('admin_activities', 2);
    }

    #[Test]
    #[TestWith(['guest'])]
    #[TestWith(['user'])]
    #[TestWith(['disabled_admin'])]
    public function reconciliation_rejects_unauthorized_actors_without_changes(string $role): void
    {
        [$admin, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        $actor = match ($role) {
            'guest' => null,
            'user' => User::factory()->create(),
            default => User::factory()->admin()->create(['deleted_at' => now()]),
        };
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'uncertain']);
        try {
            app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'confirmed');
            $this->fail('Reconciliation requires an active administrator.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            $this->assertSame(403, $exception->status() ?? 403);
        }
        $this->assertSame('uncertain', $attempt->fresh()->state);
        $this->assertSame('open', $order->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 1);
    }

    #[Test]
    public function reconciliation_rejects_unknown_outcomes_and_attempts_of_other_orders(): void
    {
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'uncertain']);
        try {
            app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'retry');
            $this->fail('Only the two explicit provider results are valid.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('outcome', $exception->errors());
        }
        $this->assertRejected(409, fn () => app(AutoOrder::class)->reconcile($actor, Order::factory()->create(), $attempt->id, 'confirmed'));
        $this->assertSame('uncertain', $attempt->fresh()->state);
        $this->assertDatabaseCount('admin_activities', 1);
    }

    #[Test]
    public function a_worker_delayed_after_reservation_cannot_send_a_reconciled_attempt_or_restore_uncertainty(): void
    {
        Http::preventStrayRequests();
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        \Illuminate\Support\Facades\Event::listen('eloquent.created: '.AdminActivity::class, function (AdminActivity $attempt) use ($actor, $order): void {
            if ($attempt->action !== 'order.auto_ordered') {
                return;
            }
            DB::afterCommit(function () use ($attempt, $actor, $order): void {
                $attempt->update(['created_at' => now()->subMinutes(31)]);
                app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'not_received');
            });
        });

        $this->assertRejected(409, fn () => app(AutoOrder::class)->execute($actor, $order));

        $this->assertSame('failed', AdminActivity::where('action', 'order.auto_ordered')->sole()->state);
        $this->assertDatabaseCount('admin_activities', 2);
        $this->assertSame('open', $order->fresh()->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_failed_reconciliation_log_rolls_back_both_the_attempt_and_order_status(): void
    {
        [$actor, $order, $provider] = $this->autoOrderFixture();
        $provider->shouldNotReceive('placeOrder', 'updateOrder');
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'uncertain',
            'changes' => ['before' => ['status' => 'open', 'content_fingerprint' => $order->contentFingerprint()], 'after' => []]]);
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.AdminActivity::class, function (AdminActivity $activity): void {
            if ($activity->action === 'order.auto_order_reconciled') {
                throw new RuntimeException('Audit storage failed.');
            }
        });
        try {
            app(AutoOrder::class)->reconcile($actor, $order, $attempt->id, 'confirmed');
            $this->fail('A failed audit write must roll back reconciliation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit storage failed.', $exception->getMessage());
        }
        $this->assertSame('uncertain', $attempt->fresh()->state);
        $this->assertSame('open', $order->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 1);
    }

    private function assertRejected(int $status, \Closure $operation): void
    {
        try {
            $operation();
            $this->fail('The autoorder must be refused.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    /** @return array{User, Order, \Mockery\MockInterface} */
    private function autoOrderFixture(): array
    {
        config(['services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);
        $actor = User::factory()->admin()->create();
        $order = Order::factory()->create(['provider' => 'Holzke']);
        $meal = Meal::factory()->inFuture()->create(['provider' => 'Holzke', 'external_id' => 'meal-1']);
        OrderItem::factory()->create(['order_id' => $order->id, 'meal_id' => $meal->id]);

        $provider = $this->partialMock(Holzke::class);
        $provider->shouldReceive('getKey')->andReturn('Holzke');

        return [$actor, $order, $provider];
    }
}
