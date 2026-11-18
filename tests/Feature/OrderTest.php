<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_are_not_allowed_to_see_orders()
    {
        $this->withExceptionHandling();

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->getJson(route('orders.index'))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function users_are_not_allowed_to_see_orders()
    {
        $this->withExceptionHandling();

        $this->login();

        $this->get(route('orders.index'))->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_provides_a_list_of_not_empty_orders()
    {
        $orders = Order::factory()->count(5)->create();
        foreach ($orders as $order) {
            OrderItem::factory()->create(['order_id' => $order->id]);
        }
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        $page = Livewire::test(ManageOrders::class)->assertCanSeeTableRecords($orders);
        foreach ($orders as $order) {
            $page->assertSee((string) $order->provider)->assertSee((string) $order->subtotal);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dont_shows_empty_orders()
    {
        $orders = Order::factory()->count(5)->create();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrders::class)->assertCanNotSeeTableRecords($orders)->assertCountTableRecords(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_provides_a_sum_of_order_items_prices()
    {
        $meal = Meal::factory()->create(['date' => today(), 'price' => 111]);
        OrderItem::factory()->create(['meal_id' => $meal->id, 'quantity' => 2]);
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrders::class)->assertSee((string) money(222));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_to_update_order_status()
    {
        $item = OrderItem::factory()->create();
        $order = $item->order;
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrders::class)->callTableAction('change', $order, data: ['status' => Order::STATUS_ORDERED])->assertHasNoActionErrors();

        $this->assertSame(Order::STATUS_ORDERED, $order->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_to_delete_a_order()
    {
        $item = OrderItem::factory()->create();
        $order = $item->order;
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrders::class)->callTableAction('remove', $order)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($order);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_the_previous_status()
    {
        /** @var Order $order */
        $order = Order::factory()->create(['status' => Order::STATUS_OPEN]);

        $order->markOrdered();

        $order->refresh();

        $this->assertEquals(Order::STATUS_ORDERED, $order->status);
        $this->assertEquals(Order::STATUS_OPEN, $order->previous_status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_order_details()
    {
        $this->travelTo(now()->startOfWeek()->addDays(2));
        $user1 = User::factory()->create();
        $user2 = User::factory()->create(['deleted_at' => now()]);
        $meal1 = Meal::factory()->create(['date' => now()->subDay()]);
        $meal2 = Meal::factory()->create(['date' => now()->addDay()]);
        $user1->order($meal1);
        $user1->order($meal2);
        $user2->order($meal1);
        $user2->order($meal2);
        $order = Order::first();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrderItems::class)->filterTable('order_id', $order->id)
            ->assertSee($meal1->date->format('d.m.Y'))->assertSee($meal2->date->format('d.m.Y'))
            ->assertSee($meal1->title)->assertSee($meal2->title)->assertSee($user1->name)->assertSee($user2->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_to_mark_an_order_as_payed_by_a_user(){
        $user1 = User::factory()->create();
        $meal1 = Meal::factory()->create(['date' => now()->subDay()]);
        $user1->order($meal1);
        $this->assertEquals($meal1->price, $user1->balance->absolute());
        $order = Order::first();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageOrders::class)->callTableAction('change', $order, data: ['user_id' => $user1->id])->assertHasNoActionErrors();

        $this->assertEquals(Money::parse(0), $user1->fresh()->balance);
    }
}
