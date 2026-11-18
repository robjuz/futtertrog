<?php

namespace Tests\Feature;

use App\Actions\Admin\AutoOrder;
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
    private string $fixturePath;
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        $this->fixturePath = tempnam(sys_get_temp_dir(), 'admin-autoorder-');
        $connection = array_merge(config('database.connections.sqlite'), ['database' => $this->fixturePath]);
        config(['database.connections.auto_order_fixture' => $connection,
            'database.connections.auto_order_observer' => $connection]);
        $this->artisan('migrate:fresh', ['--database' => 'auto_order_fixture', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        config(['database.default' => 'auto_order_fixture']);
    }

    protected function tearDown(): void
    {
        config(['database.default' => $this->originalConnection]);
        DB::purge('auto_order_observer');
        DB::purge('auto_order_fixture');
        unlink($this->fixturePath);
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
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target) use ($order, &$calls): void {
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
