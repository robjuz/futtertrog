<?php

namespace Tests\Feature;

use App\Events\OrderReopened;
use App\Notifications\OrderReopenedNotification;
use App\Order;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MealOrderingTest extends TestCase
{
    /** @test */
    public function user_can_order_a_meal_for_himself()
    {
        $meal = factory('App\Meal')->create();

        $this->login();
        $this->post(route('order_items.store'), [
            'date' => $meal->date_from->toDateString(),
            'meal_id' => $meal->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());

        $this->postJson(route('order_items.store'), [
            'date' => $meal->date_from->toDateString(),
            'meal_id' => $meal->id
        ])->assertSuccessful();
    }

    /** @test */
    public function user_cannot_order_a_meal_for_other_users()
    {
        $meal = factory('App\Meal')->create();

        $user = factory('App\User')->create();

        $this->login();

        $this->post(route('order_items.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    public function admin_can_order_a_meal_for_other_users()
    {
        $meal = factory('App\Meal')->create();

        $user = factory('App\User')->create();

        $this->loginAsAdmin()
            ->post(route('orders.store'), [
                'date' => $meal->date_from,
                'user_id' => $user->id,
                'meal_id' => $meal->id
            ]);

        $this->assertFalse(auth()->user()->meals->contains($meal));
        $this->assertTrue($user->meals->contains($meal));
    }

    /** @test */
    public function guests_cannot_order_meals()
    {
        $this->withExceptionHandling();

        $this->post(route('order_items.store'))->assertRedirect(route('login'));
    }

    /** @test */
    public function it_dispatches_an_event_when_an_order_was_reopened()
    {
        $meal = factory('App\Meal')->create();
        $user = factory('App\User')->create();

        // Given we have a closed order
        $order = factory('App\Order')->create([
            'date' => $meal->date_from,
            'status' => Order::STATUS_ORDERED,
            'provider' => $meal->provider
        ]);

        Event::fake();

        // When a user creates a new order item associated with this order
        $this->login($user);
        $this->post(route('order_items.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        // En event is dispatched
        Event::assertDispatched(OrderReopened::class, function ($event) use ($order, $user, $meal) {
            return $event->order->id === $order->id
                && $event->user->id === $user->id
                && $event->meal->id === $meal->id;
        });
    }

    /** @test */
    public function it_notifies_an_admin_when_an_closed_was_reopened()
    {
        $meal = factory('App\Meal')->create();
        $user = factory('App\User')->create();

        $admin = factory('App\User')->create(['is_admin' => true]);

        // Given we have a closed order
        $order = factory('App\Order')->create([
            'date' => $meal->date_from,
            'status' => Order::STATUS_ORDERED,
            'provider' => $meal->provider
        ]);


        Notification::fake();
        Mail::fake();

        // When a user creates a new order item associated with this order
        $this->login($user);
        $this->post(route('order_items.store'), [
            'date' => $meal->date_from,
            'user_id' => $user->id,
            'meal_id' => $meal->id
        ]);

        // Admin should be notified
        Notification::assertSentTo(
            $admin,
            OrderReopenedNotification::class,
            function ($notification, $channels) use ($user, $meal, $order) {
                /** @var \Illuminate\Notifications\Messages\MailMessage $mailData */
                $mailData = $notification->toMail($user);
                $this->assertEquals(__('Order reopened'), $mailData->subject);

                $arrayData = $notification->toArray($user);
                $this->assertEquals($arrayData['date'], $order->date);
                $this->assertEquals($arrayData['user'], $user->name);
                $this->assertEquals($arrayData['meal'], $meal->title);

                return $notification->order->is($order)
                    && $notification->user->is($user)
                    && $notification->meal->is($meal);
            });

    }
}
