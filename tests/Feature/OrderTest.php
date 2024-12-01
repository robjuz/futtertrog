<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /** @test */
    public function guests_are_not_allowed_to_see_orders()
    {
        $this->withExceptionHandling();

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->getJson(route('orders.index'))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function users_are_not_allowed_to_see_orders()
    {
        $this->withExceptionHandling();

        $this->login();

        $this->get(route('orders.index'))->assertForbidden();
    }

    /** @test */
    public function it_provides_a_list_of_not_empty_orders()
    {
        /** @var \Illuminate\Support\Collection|\App\Models\Order[] $orders */
        $orders = Order::factory()->count(5)->create();

        $orders->each(function($order) {
            /** @var OrderItem $orderItem */
            $orderItem = OrderItem::factory()->make();

            /** @var Meal $meal */
            $meal = Meal::factory()->create(['provider'=> $order->provider->getKey()]);

            $orderItem->meal()->associate($meal);

            $order->orderItems()->save($orderItem);
        });

        $this->loginAsAdmin();

        $response = $this->get(route('orders.index'));
        $jsonResponse = $this->getJson(route('orders.index'));


        foreach ($orders as $order) {
            $response->assertSee($order->provider);
            $response->assertSee($order->subtotal);

            $jsonResponse->assertJsonFragment([
                'provider' => $order->provider->getName(),
                'subtotal' => $order->subtotal
            ]);
        }
    }

    /** @test */
    public function it_dont_shows_empty_orders()
    {
        /** @var \Illuminate\Support\Collection|\App\Models\Order[] $orders */
        $orders = Order::factory()->count(5)->create();

        $this->loginAsAdmin();

        $response = $this->get(route('orders.index'));
        $jsonResponse = $this->getJson(route('orders.index'));


        foreach ($orders as $order) {
            $response->assertDontSee($order->subtotal);

            $jsonResponse->assertJsonMissing([
                'provider' => $order->provider,
                'subtotal' => $order->subtotal
            ]);
        }
    }

    /** @test */
    public function it_provides_a_sum_of_order_items_prices()
    {
        $meal = Meal::factory()->create([
            'date' => today(),
            'price' => 111
        ]);

        OrderItem::factory()->create([
            'meal_id' => $meal->id,
            'quantity' => 2
        ]);

        $this->loginAsAdmin()
            ->get(route('orders.index'))
            ->assertSee(money(222));
    }

    /** @test */
    public function it_allows_to_update_order_status()
    {
        /** @var \App\Models\Order $order */
        $order = Order::factory()->create(['status' => Order::STATUS_OPEN]);
        $this->loginAsAdmin()
            ->put(route('orders.update', $order), ['status' => Order::STATUS_ORDERED])
            ->assertRedirect();
        $this->assertEquals(Order::STATUS_ORDERED, $order->fresh()->status);

        $order = Order::factory()->create(['status' => Order::STATUS_OPEN]);
        $this->loginAsAdmin()
            ->putJson(route('orders.update', $order), ['status' => Order::STATUS_ORDERED])
            ->assertSuccessful()
            ->assertJson($order->fresh()->toArray());
        $this->assertEquals(Order::STATUS_ORDERED, $order->fresh()->status);
    }

    /** @test */
    public function it_allows_to_delete_a_order()
    {
        /** @var \App\Models\Order $order */
        $order = Order::factory()->create();
        $this->loginAsAdmin()
            ->delete(route('orders.destroy', $order))
            ->assertRedirect();
        $this->assertDatabaseMissing('orders', $order->setAppends([])->toArray());

        /** @var \App\Models\Order $order */
        $order = Order::factory()->create();
        $this->loginAsAdmin()
            ->deleteJson(route('orders.destroy', $order))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('orders', $order->setAppends([])->toArray());
    }

    /** @test */
    public function it_stores_the_previous_status()
    {
        /** @var Order $order */
        $order = Order::factory()->create(['status' => Order::STATUS_OPEN]);

        $order->markOrdered();

        $order->refresh();

        $this->assertEquals(Order::STATUS_ORDERED, $order->status);
        $this->assertEquals(Order::STATUS_OPEN, $order->previous_status);
    }

    /** @test */
    public function it_shows_order_details()
    {
        Carbon::setTestNow(now()->startOfWeek()->addDays(2));

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user2->delete();


        $meal1 = Meal::factory()->create(['date' => now()->subDay()]);
        $meal2 = Meal::factory()->create(['date' => now()->addDay()]);

        $user1->order($meal1);
        $user1->order($meal2);
        $user2->order($meal1);
        $user2->order($meal2);

        $order = Order::first();

        $this->loginAsAdmin()
            ->get(route('orders.edit', $order))
            ->assertSee($meal1->date->isoFormat('L'))
            ->assertSee($meal2->date->isoFormat('L'))
            ->assertSee($meal1->title)
            ->assertSee($meal2->title)
            ->assertSee($user1->username)
            ->assertSee($user2->username);

    }

    /**
     * @test
     */
    public function it_allows_to_mark_an_order_as_payed_by_a_user(){
        $user1 = User::factory()->create();

        $meal1 = Meal::factory()->create(['date' => now()->subDay()]);

        $user1->order($meal1);

        $this->assertEquals($meal1->price, $user1->balance->absolute());

        $order = Order::first();

        $this->loginAsAdmin()
            ->put(route('orders.update', $order), ['user_id' => $user1->id]);

        $this->assertEquals(Money::parse(0), $user1->fresh()->balance);
    }
}
