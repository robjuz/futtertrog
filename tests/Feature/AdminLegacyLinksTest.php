<?php

namespace Tests\Feature;

use App\Filament\Pages\Notifications;
use App\Filament\Resources\{Deposits\DepositResource, Meals\MealResource, OrderItems\OrderItemResource, Orders\OrderResource};
use App\Models\{Deposit, Meal, OrderItem, User};
use Illuminate\Support\Facades\{Mail, Notification};
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use Tests\TestCase;

class AdminLegacyLinksTest extends TestCase
{
    #[Test]
    public function old_admin_get_links_keep_named_routes_and_open_the_appropriate_panel_dialog(): void
    {
        $this->loginAsAdmin();
        $item = OrderItem::factory()->create();
        $deposit = Deposit::factory()->create();
        $targets = [
            ['admin.meals.index', [], MealResource::getUrl()],
            ['meals.edit', [$item->meal_id], MealResource::getUrl(parameters: ['tableAction' => 'change', 'tableActionRecord' => $item->meal_id])],
            ['orders.index', [], OrderResource::getUrl()],
            ['orders.edit', [$item->order_id], OrderResource::getUrl(parameters: ['tableAction' => 'change', 'tableActionRecord' => $item->order_id])],
            ['admin.order_items.index', [], OrderItemResource::getUrl()],
            ['admin.order_items.create', [], OrderItemResource::getUrl(parameters: ['action' => 'orderFor'])],
            ['admin.order_items.edit', [$item->id], OrderItemResource::getUrl(parameters: ['tableAction' => 'change', 'tableActionRecord' => $item->id])],
            ['deposits.index', [], DepositResource::getUrl()],
            ['deposits.create', [], DepositResource::getUrl(parameters: ['action' => 'book'])],
            ['deposits.edit', [$deposit->id], DepositResource::getUrl(parameters: ['tableAction' => 'change', 'tableActionRecord' => $deposit->id])],
            ['deposits.transfer.create', [], DepositResource::getUrl(parameters: ['action' => 'transfer'])],
            ['notifications.create', [], Notifications::getUrl(['action' => 'send'])],
        ];

        foreach ($targets as [$name, $parameters, $target]) {
            $this->get(route($name, $parameters))->assertRedirect($target);
            $this->get($target)->assertOk();
        }
        $this->get('/admin/panel')->assertRedirect(route('filament.administration.pages.dashboard'));
        $this->get('/orders?from=2026-11-18&status=open')->assertRedirect('/admin/orders?from=2026-11-18&status=open');
        $this->get('/admin/orders?from=2026-11-18&status=open')->assertRedirect(OrderResource::getUrl(parameters: ['filters' => ['status' => ['value' => 'open'], 'dates' => ['from' => '2026-11-18', 'until' => null]]]));
        $this->get('/admin/order_items?user_id='.$item->user_id)->assertRedirect(OrderItemResource::getUrl(parameters: ['filters' => ['user_id' => ['value' => $item->user_id]]]));
        $this->get(route('admin.index'))->assertRedirect(route('filament.administration.pages.dashboard'));
    }

    public static function closedWrites(): iterable
    {
        foreach ([['POST', 'meals/import'], ['PUT', 'meals/1'], ['PATCH', 'meals/1'], ['DELETE', 'meals/1'],
            ['POST', 'orders/1/auto_order'], ['PUT', 'orders/1'], ['DELETE', 'orders/1'],
            ['POST', 'order_items'], ['PUT', 'order_items/1'], ['DELETE', 'order_items/1'],
            ['POST', 'deposits'], ['POST', 'deposits/transfer'], ['PUT', 'deposits/1'], ['DELETE', 'deposits/1'], ['POST', 'notifications']] as [$method, $path]) {
            yield "$method $path" => [$method, '/admin/'.$path];
        }
    }

    #[Test]
    #[DataProvider('closedWrites')]
    public function replaced_admin_writes_return_410_and_have_no_effect(string $method, string $url): void
    {
        $this->loginAsAdmin()->withExceptionHandling();
        Mail::fake();
        Notification::fake();

        $this->json($method, $url, ['value' => 1234, 'quantity' => 10, 'is_admin' => true])->assertStatus(410);

        $this->assertDatabaseCount('deposits', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }
}
