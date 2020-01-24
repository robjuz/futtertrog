<?php

namespace Tests\Feature;

use App\Events\OrderReopened;
use App\Meal;
use App\Notifications\OrderReopenedNotification;
use App\Order;
use App\OrderItem;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MealOrderingTest extends TestCase
{
    /** @test */
    public function user_can_order_a_meal_for_himself()
    {
        $meal = factory(Meal::class)->create([
            'date_from' => today()->addDay(),
            'date_to' => today()->addDay()
        ]);

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
        $meal = factory(Meal::class)->state('in_future')->create();

        $user = factory(User::class)->create();

        $this->login()
            ->post(route('order_items.store'), [
                'date' => $meal->date_from,
                'user_id' => $user->id,
                'meal_id' => $meal->id
            ]);

        $this->assertFalse($user->orderItems()->where('meal_id', $meal->id)->exists());
        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    /** @test */
    public function user_cannot_order_a_meal_located_in_the_past()
    {
        $meal = factory(Meal::class)->create([
            'date_from' => today(),
            'date_to' => today()
        ]);

        $this->login()
            ->withExceptionHandling()
            ->post(route('order_items.store'), [
                'date' => $meal->date_from,
                'meal_id' => $meal->id
            ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_order_a_meal_for_other_users()
    {
        $meal = factory(Meal::class)->create();

        /** @var \App\User $user */
        $user = factory(User::class)->create();

        $this->loginAsAdmin()
            ->post(route('order_items.store'), [
                'date' => $meal->date_from,
                'user_id' => $user->id,
                'meal_id' => $meal->id
            ]);

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
        $this->assertTrue($user->orderItems()->where('meal_id', $meal->id)->exists());
    }

    /** @test */
    public function guests_cannot_order_meals()
    {
        $this->withExceptionHandling();

        $this->post(route('order_items.store'))->assertRedirect(route('login'));
        $this->postJson(route('order_items.store'))->assertUnauthorized();
    }

    /** @test */
    public function it_dispatches_an_event_when_an_order_was_reopened()
    {
        $meal = factory(Meal::class)->state('in_future')->create();
        $user = factory(User::class)->create();

        // Given we have a closed order
        $order = factory(Order::class)->create([
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
        $meal = factory(Meal::class)->state('in_future')->create();
        $user = factory(User::class)->create();

        $admin = factory(User::class)->create(['is_admin' => true]);

        // Given we have a closed order
        $order = factory(Order::class)->create([
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

                $toArray = $notification->toArray($user);
                $this->assertEquals($toArray['date'], $order->date);
                $this->assertEquals($toArray['user'], $user->name);
                $this->assertEquals($toArray['meal'], $meal->title);

                $toWebPush = $notification->toWebPush($user)->toArray();
                $this->assertEquals($toWebPush['title'], __('The order for :date was reopened', ['date' => $order->date->format(trans('futtertrog.date_format'))]));
                $this->assertEquals($toWebPush['body'], __(':user updated :meal', ['user' => $user->name, 'meal' => $meal->title]));

                return $notification->order->is($order)
                    && $notification->user->is($user)
                    && $notification->meal->is($meal);
            }
        );
    }

    /** @test */
    public function it_provides_a_list_of_order_items()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->make();
        $user->orderItems()->save($orderItem);

        $orderItem->load('meal');

        $this->login($user)
            ->get(route('order_items.index'))
            ->assertJsonFragment($orderItem->toArray());
    }

    /** @test */
    public function admin_can_see_all_order_items()
    {
        $orderItem = factory(OrderItem::class)->create();

        $this->loginAsAdmin()
            ->get(route('order_items.index'))
            ->assertJsonFragment($orderItem->toArray());
    }

    /** @test */
    public function admin_can_see_order_items_from_other_users()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->make();
        $user->orderItems()->save($orderItem);

        $orderItem2 = factory(OrderItem::class)->create();

        $orderItem->load('meal');

        $this->loginAsAdmin()
            ->get(route('order_items.index', ['user_id' => $user->id]))
            ->assertJsonFragment($orderItem->toArray())
            ->assertJsonMissingExact(['user_id' => $orderItem2->user_id]);
    }

    /** @test */
    public function it_allows_to_delete_an_order_item()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->state('in_future')->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->delete(route('order_items.destroy', $orderItem))
            ->assertRedirect();

        $this->assertDatabaseMissing('order_items', $orderItem->toArray());

        $orderItem = factory(OrderItem::class)->state('in_future')->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->deleteJson(route('order_items.destroy', $orderItem))
            ->assertSuccessful();

        $this->assertDatabaseMissing('order_items', $orderItem->toArray());
    }

    /** @test */
    public function it_disallows_to_delete_an_order_item_in_the_past()
    {
        $user = factory(User::class)->create();
        $orderItem = factory(OrderItem::class)->state('in_past')->make();
        $user->orderItems()->save($orderItem);

        $this->withExceptionHandling();

        $this->login($user)
            ->delete(route('order_items.destroy', $orderItem))
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', $orderItem->toArray());

        $orderItem = factory(OrderItem::class)->state('in_past')->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->deleteJson(route('order_items.destroy', $orderItem))
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', $orderItem->toArray());
    }
}
