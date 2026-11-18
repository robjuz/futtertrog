<?php

namespace Tests\Feature;

use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use App\Filament\Resources\Meals\Pages\ManageMeals;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Models\{Deposit, Meal, Order, OrderItem, User};
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use Tests\TestCase;

class AdminResourceTablesTest extends TestCase
{
    #[Test]
    public function meal_search_provider_date_variant_filters_and_pagination_limit_the_results(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $parent = Meal::factory()->create(['title' => 'Spezialgericht', 'date' => '2026-11-19']);
        $variant = Meal::factory()->create(['parent_id' => $parent->id, 'title' => 'Variante', 'date' => '2026-11-19']);
        $other = Meal::factory()->create(['date' => '2026-11-20']);
        Meal::factory()->count(10)->create(['date' => '2026-11-20']);

        $page = Livewire::test(ManageMeals::class)->searchTable('Spezialgericht')->assertCanSeeTableRecords([$parent, $variant])->assertCanNotSeeTableRecords([$other]);
        $page->searchTable('')->filterTable('dates', ['from' => '2026-11-19', 'until' => '2026-11-19'])->assertCanSeeTableRecords([$parent, $variant])->assertCanNotSeeTableRecords([$other]);
        $page->filterTable('parent_id', true)->assertCanSeeTableRecords([$variant])->assertCanNotSeeTableRecords([$parent]);
        $page->resetTableFilters()->set('tableRecordsPerPage', 10)->assertCountTableRecords(13)->call('gotoPage', 2)->assertSet('paginators.page', 2);
    }

    #[Test]
    public function order_filters_find_date_recipient_status_provider_and_payment(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $first = OrderItem::factory()->create(['meal_id' => Meal::factory()->create(['date' => '2026-11-19']), 'order_id' => Order::factory()->create(['status' => 'ordered', 'payed_at' => '2026-11-18'])]);
        $other = OrderItem::factory()->create(['meal_id' => Meal::factory()->create(['date' => '2026-11-20']), 'order_id' => Order::factory()->create(['status' => 'open'])]);

        $page = Livewire::test(ManageOrders::class)->filterTable('status', 'ordered')->assertCanSeeTableRecords([$first->order])->assertCanNotSeeTableRecords([$other->order]);
        $page->resetTableFilters()->filterTable('dates', ['from' => '2026-11-19', 'until' => '2026-11-19'])->assertCanSeeTableRecords([$first->order])->assertCanNotSeeTableRecords([$other->order]);
        $page->resetTableFilters()->filterTable('recipient', $first->user_id)->assertCanSeeTableRecords([$first->order])->assertCanNotSeeTableRecords([$other->order]);
        $page->resetTableFilters()->filterTable('payed_at', true)->assertCanSeeTableRecords([$first->order])->assertCanNotSeeTableRecords([$other->order]);
        $page->resetTableFilters()->searchTable((string) $first->order_id)->assertCanSeeTableRecords([$first->order]);
    }

    #[Test]
    public function deposit_search_user_status_and_date_filters_keep_the_correct_records(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $first = Deposit::factory()->create(['comment' => 'Finde mich', 'status' => 'ok', 'created_at' => '2026-11-18']);
        $other = Deposit::factory()->create(['status' => 'processing', 'created_at' => '2026-11-17']);

        $page = Livewire::test(ManageDeposits::class)->searchTable('Finde mich')->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->searchTable('')->filterTable('user_id', $first->user_id)->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->resetTableFilters()->filterTable('status', 'ok')->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->resetTableFilters()->filterTable('dates', ['from' => '2026-11-18', 'until' => '2026-11-18'])->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
    }

    #[Test]
    public function order_item_search_and_order_user_date_filters_keep_the_correct_records(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $first = OrderItem::factory()->create(['meal_id' => Meal::factory()->create(['title' => 'Finde Gericht', 'date' => '2026-11-19'])]);
        $other = OrderItem::factory()->create(['meal_id' => Meal::factory()->create(['date' => '2026-11-20'])]);

        $page = Livewire::test(ManageOrderItems::class)->searchTable('Finde Gericht')->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->searchTable('')->filterTable('order_id', $first->order_id)->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->resetTableFilters()->filterTable('user_id', $first->user_id)->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
        $page->resetTableFilters()->filterTable('dates', ['from' => '2026-11-19', 'until' => '2026-11-19'])->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$other]);
    }

    #[Test]
    public function tables_render_relations_without_lazy_loading_and_escape_user_input(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $parent = Meal::factory()->create(['title' => '<script>parent</script>']);
        $user = User::factory()->create(['name' => '<script>user</script>']);
        foreach (range(1, 3) as $number) {
            $meal = Meal::factory()->create(['parent_id' => $parent->id, 'title' => '<script>variant</script>']);
            OrderItem::factory()->create(['meal_id' => $meal->id, 'user_id' => $user->id]);
            Deposit::factory()->create(['user_id' => $user->id, 'comment' => '<script>comment</script>']);
        }
        Model::preventLazyLoading();
        try {
            foreach ([ManageMeals::class, ManageOrders::class, ManageOrderItems::class, ManageDeposits::class] as $page) {
                Livewire::test($page)->assertOk()->assertDontSeeHtml('<script>variant</script>')->assertDontSeeHtml('<script>user</script>')->assertDontSeeHtml('<script>comment</script>');
            }
        } finally {
            Model::preventLazyLoading(false);
        }
    }
    public static function paginatedPages(): iterable
    {
        yield 'deposits' => [ManageDeposits::class];
        yield 'orders' => [ManageOrders::class];
        yield 'positions' => [ManageOrderItems::class];
    }

    #[Test]
    #[DataProvider('paginatedPages')]
    public function each_table_paginates_without_losing_records(string $page): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $user = User::factory()->create();
        $meal = Meal::factory()->create();
        foreach (range(1, 12) as $number) {
            Deposit::factory()->create(['user_id' => $user->id]);
            OrderItem::factory()->create(['user_id' => $user->id, 'meal_id' => $meal->id]);
        }

        $component = Livewire::test($page)->set('tableRecordsPerPage', 10)->assertCountTableRecords(12);
        $this->assertCount(10, $component->instance()->getTableRecords());
        $component->call('gotoPage', 2);
        $this->assertCount(2, $component->instance()->getTableRecords());
    }

    #[Test]
    public function provider_filters_distinguish_manual_and_imported_meals_orders_and_positions(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        config(['services.holzke.enabled' => true]);
        $this->app->register(\App\Providers\MealProvidersServiceProvider::class, true);
        $manual = OrderItem::factory()->create();
        $external = OrderItem::factory()->create([
            'meal_id' => Meal::factory()->create(['provider' => 'Holzke']),
            'order_id' => Order::factory()->create(['provider' => 'Holzke']),
        ]);

        Livewire::test(ManageMeals::class)->filterTable('provider', 'Holzke')->assertCanSeeTableRecords([$external->meal])->assertCanNotSeeTableRecords([$manual->meal]);
        Livewire::test(ManageOrders::class)->filterTable('provider', 'Holzke')->assertCanSeeTableRecords([$external->order])->assertCanNotSeeTableRecords([$manual->order]);
        Livewire::test(ManageOrderItems::class)->filterTable('provider', 'internal')->assertCanSeeTableRecords([$manual])->assertCanNotSeeTableRecords([$external]);
    }

}
