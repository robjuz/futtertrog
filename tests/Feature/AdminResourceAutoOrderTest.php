<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\MealProviders\Holzke;
use App\Models\{AdminActivity, Meal, Order, OrderItem};
use App\Providers\MealProvidersServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\{DB, Http};
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{Test, TestWith};
use RuntimeException;
use Tests\TestCase;

class AdminResourceAutoOrderTest extends TestCase
{
    private string $fixturePath;
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        $this->fixturePath = tempnam(sys_get_temp_dir(), 'panel-autoorder-');
        config(['database.connections.panel_auto_order' => array_merge(config('database.connections.sqlite'), ['database' => $this->fixturePath])]);
        $this->artisan('migrate:fresh', ['--database' => 'panel_auto_order', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        config(['database.default' => 'panel_auto_order', 'services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);
        Filament::setCurrentPanel(Filament::getPanel('administration'));
    }

    protected function tearDown(): void
    {
        config(['database.default' => $this->originalConnection]);
        DB::purge('panel_auto_order');
        unlink($this->fixturePath);
        parent::tearDown();
    }

    private function order(): Order
    {
        $meal = Meal::factory()->inFuture()->create(['provider' => 'Holzke', 'external_id' => 'meal-1']);
        $order = Order::factory()->create(['provider' => 'Holzke', 'status' => Order::STATUS_OPEN]);
        OrderItem::factory()->create(['meal_id' => $meal->id, 'order_id' => $order->id]);

        return $order;
    }

    #[Test]
    #[TestWith(['confirmed', 'ordered'])]
    #[TestWith(['not_received', 'open'])]
    public function panel_filters_uncleared_orders_and_reconciles_the_selected_attempt(string $outcome, string $status): void
    {
        Http::preventStrayRequests();
        $this->loginAsAdmin();
        $order = $this->order();
        $clear = $this->order();
        $attempt = AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class,
            'target_id' => $order->id, 'state' => 'uncertain']);
        $this->partialMock(Holzke::class)->shouldNotReceive('placeOrder', 'updateOrder');
        $page = Livewire::test(ManageOrders::class)->filterTable('auto_order_pending')
            ->assertCanSeeTableRecords([$order])->assertCanNotSeeTableRecords([$clear])
            ->assertSee('Abgleich erforderlich');
        $page->callTableAction('reconcileAutoOrder', $order, data: ['attempt_id' => $attempt->id, 'outcome' => $outcome])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $this->assertSame($status, $order->fresh()->status);
        $this->assertSame(auth()->id(), AdminActivity::where('action', 'order.auto_order_reconciled')->sole()->actor_id);
        $page->assertCanNotSeeTableRecords([$order]);
        Http::assertNothingSent();
    }

    #[Test]
    public function panel_autoorder_commits_its_reservation_and_never_repeats_a_confirmed_order(): void
    {
        Http::preventStrayRequests();
        $this->loginAsAdmin();
        $order = $this->order();
        $provider = $this->partialMock(Holzke::class);
        $provider->shouldReceive('getKey')->andReturn('Holzke');
        $provider->shouldReceive('placeOrder')->once()->andReturnUsing(function (Order $target): void {
            $this->assertSame(1, DB::transactionLevel());
            $this->assertSame('running', AdminActivity::sole()->state);
            $target->markOrdered();
        });
        $page = Livewire::test(ManageOrders::class);

        $page->callTableAction('autoOrder', $order)->assertNotified('Vorgang erfolgreich.');
        $page->callTableAction('autoOrder', $order)->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertSame('succeeded', AdminActivity::sole()->state);
        Http::assertNothingSent();
    }

    #[Test]
    #[TestWith(['running'])]
    #[TestWith(['uncertain'])]
    public function running_or_uncertain_autoorders_display_409_and_do_not_contact_the_provider(string $state): void
    {
        Http::preventStrayRequests();
        $this->loginAsAdmin();
        $order = $this->order();
        $this->partialMock(Holzke::class)->shouldNotReceive('placeOrder', 'updateOrder');
        AdminActivity::factory()->create(['action' => 'order.auto_ordered', 'target_type' => Order::class, 'target_id' => $order->id, 'state' => $state]);

        Livewire::test(ManageOrders::class)->callTableAction('autoOrder', $order)->assertNotified(
            \Filament\Notifications\Notification::make()->danger()->title('Vorgang nicht ausgeführt.')
                ->body('Der Vorgang läuft bereits, wurde mit anderen Daten gesendet oder sein Ergebnis ist unklar (409). Bitte vor einem neuen Versuch klären.')
        );

        $this->assertSame('open', $order->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 1);
        Http::assertNothingSent();
    }

    #[Test]
    public function provider_failures_show_a_safe_error_and_preserve_uncertainty_without_a_success_entry(): void
    {
        Http::preventStrayRequests();
        $this->loginAsAdmin();
        $order = $this->order();
        $this->partialMock(Holzke::class)->shouldReceive('placeOrder')->once()->andThrow(new RuntimeException('private-provider-token'));

        $page = Livewire::test(ManageOrders::class)->callTableAction('autoOrder', $order)->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertStringNotContainsString('private-provider-token', $page->html());
        $this->assertSame('open', $order->fresh()->status);
        $this->assertSame('uncertain', AdminActivity::sole()->state);
        $this->assertSame([], AdminActivity::sole()->changes['after']);
        Http::assertNothingSent();
    }
}
