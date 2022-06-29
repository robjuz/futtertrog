<?php

namespace Tests\Feature;

use App\Meal;
use App\Order;
use App\OrderItem;
use Illuminate\Http\Response;
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
        /** @var \Illuminate\Support\Collection|\App\Order[] $orders */
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
        /** @var \Illuminate\Support\Collection|\App\Order[] $orders */
        $orders = Order::factory()->count(5)->create();

        $this->loginAsAdmin();

        $response = $this->get(route('orders.index'));
        $jsonResponse = $this->getJson(route('orders.index'));


        foreach ($orders as $order) {
            $response->assertDontSee($order->provider);

            $jsonResponse->assertJsonMissing([
                'provider' => $order->provider,
                'subtotal' => $order->subtotal
            ]);
        }
    }

    /** @test */
    public function it_shows_per_default_today_and_upcoming_orders()
    {
        /** @var OrderItem $pastOrder */
        $pastOrder = OrderItem::factory()->inPast()->create();
        /** @var OrderItem $todayOrder */
        $todayOrder = OrderItem::factory()->create();
        /** @var OrderItem $upcomingOrder */
        $upcomingOrder = OrderItem::factory()->inFuture()->create();

        $this->loginAsAdmin()
            ->get(route('orders.index'))
            ->assertSee($todayOrder->date->isoFormat('L'))
            ->assertSee($upcomingOrder->date->isoFormat('L'))
            ->assertDontSee($pastOrder->date->isoFormat('L'));
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
        /** @var \App\Order $order */
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
        /** @var \App\Order $order */
        $order = Order::factory()->create();
        $this->loginAsAdmin()
            ->delete(route('orders.destroy', $order))
            ->assertRedirect();
        $this->assertDatabaseMissing('orders', $order->setAppends([])->toArray());

        /** @var \App\Order $order */
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
}
