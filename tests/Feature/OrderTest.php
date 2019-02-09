<?php

namespace Tests\Feature;

use App\Order;
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
    public function it_provides_a_list_of_orders()
    {
        /** @var \Illuminate\Support\Collection|\App\Order[] $orders */
        $orders = factory('App\Order', 5)->create([
            'date' => today()
        ]);

        $this->loginAsAdmin();

        $response = $this->get(route('orders.index'));
        $jsonResponse = $this->getJson(route('orders.index'));


        foreach ($orders as $order) {
            $response->assertSee($order->date->format(trans('futtertrog.date_format')));
            $response->assertSee($order->provider);

            $jsonResponse->assertJsonFragment([
                'date' => $order->date->toDateTimeString(),
                'provider' => $order->provider,
                'subtotal' => $order->subtotal
            ]);
        }
    }

    /** @test */
    public function it_shows_per_default_today_and_upcoming_orders()
    {
        $yesterdayOrder = factory('App\Order')->create(['date' => today()->subDay()]);
        $todayOrder = factory('App\Order')->create(['date' => today()]);
        $tomorrowOrder = factory('App\Order')->create(['date' => today()->addDay()]);

        $this->loginAsAdmin()
            ->get(route('orders.index'))
            ->assertSee($todayOrder->date->format(trans('futtertrog.date_format')))
            ->assertSee($tomorrowOrder->date->format(trans('futtertrog.date_format')))
            ->assertDontSee($yesterdayOrder->date->format(trans('futtertrog.date_format')));
    }

    /** @test */
    public function it_allows_to_filter_orders_by_date_range()
    {
        $yesterdayOrder = factory('App\Order')->create(['date' => today()->subDay()]);
        $todayOrder = factory('App\Order')->create(['date' => today()]);
        $tomorrowOrder = factory('App\Order')->create(['date' => today()->addDay()]);

        $this->loginAsAdmin()
            ->get(route('orders.index', [
                'from' => today()->toDateString(),
                'to' => today()->toDateString()
            ]))
            ->assertSee($todayOrder->date->format(trans('futtertrog.date_format')))
            ->assertDontSee($tomorrowOrder->date->format(trans('futtertrog.date_format')))
            ->assertDontSee($yesterdayOrder->date->format(trans('futtertrog.date_format')));
    }

    /** @test */
    public function it_provides_a_sum_of_order_items_prices()
    {
        /** @var \App\Order $order */
        $order = factory('App\Order')->create(['date' => today()]);

        $meal = factory('App\Meal')->create([
            'date_from' => today(),
            'date_to' => today(),
            'price' => 1.11
        ]);

        $order->orderItems()->save(factory('App\OrderItem')->make([
            'meal_id' => $meal->id,
            'quantity' => 2
        ]));

        $this->loginAsAdmin()
            ->get(route('orders.index'))
            ->assertSee(number_format(2.22, 2, ',', '.'));
    }

    /** @test */
    public function it_allows_to_update_order_status()
    {
        /** @var \App\Order $order */
        $order = factory('App\Order')->create(['status' => Order::STATUS_OPEN]);
        $this->loginAsAdmin()
            ->put(route('orders.update', $order), ['status' => Order::STATUS_ORDERED])
            ->assertRedirect();
        $this->assertEquals(Order::STATUS_ORDERED, $order->fresh()->status);

        $order = factory('App\Order')->create(['status' => Order::STATUS_OPEN]);
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
        $order = factory('App\Order')->create();
        $this->loginAsAdmin()
            ->delete(route('orders.destroy', $order))
            ->assertRedirect();
        $this->assertDatabaseMissing('orders', $order->toArray());

        /** @var \App\Order $order */
        $order = factory('App\Order')->create();
        $this->loginAsAdmin()
            ->deleteJson(route('orders.destroy', $order))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('orders', $order->toArray());
    }
}
