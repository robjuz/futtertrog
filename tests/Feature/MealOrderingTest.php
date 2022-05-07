<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Meal;
use App\Notifications\OrderReopenedNotification;
use App\Order;
use App\OrderItem;
use App\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MealOrderingTest extends TestCase
{
    /** @test */
    public function user_can_order_a_meal_for_himself()
    {
        $meal = Meal::factory()->create([
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
        $meal = Meal::factory()->inFuture()->create();

        $user = User::factory()->create();

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
        $meal = Meal::factory()->create([
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
        $meal = Meal::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->loginAsAdmin();

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());

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
        $meal = Meal::factory()->inFuture()->create();
        $user = User::factory()->create();

        // Given we have a closed order
        /** @var Order $order */
        $order = Order::factory()->create([
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
        Event::assertDispatched(OrderUpdated::class, function ($event) use ($order, $user, $meal) {
            $orderItem = $order->orderItems()->whereMealId($meal->id)->first();
            return $event->order->is($order)
                && $event->user->is($user)
                && $event->orderItem->is($orderItem);
        });
    }

    /** @test */
    public function it_provides_a_list_of_order_items()
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->make();
        $user->orderItems()->save($orderItem);

        $orderItem->load('meal');

        $this->login($user)
            ->get(route('order_items.index'))
            ->assertJsonFragment($orderItem->toArray());
    }

    /** @test */
    public function admin_can_see_all_order_items()
    {
        $orderItem = OrderItem::factory()->create();

        $this->loginAsAdmin()
            ->get(route('order_items.index'))
            ->assertJsonFragment($orderItem->toArray());
    }

    /** @test */
    public function admin_can_see_order_items_from_other_users()
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->make();
        $user->orderItems()->save($orderItem);

        $orderItem2 = OrderItem::factory()->create();

        $orderItem->load('meal');

        $this->loginAsAdmin()
            ->get(route('order_items.index', ['user_id' => $user->id]))
            ->assertJsonFragment($orderItem->toArray())
            ->assertJsonMissingExact(['user_id' => $orderItem2->user_id]);
    }

    /** @test */
    public function it_allows_to_delete_an_order_item()
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->inFuture()->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->delete(route('order_items.destroy', $orderItem))
            ->assertRedirect();

        $this->assertDatabaseMissing('order_items', $orderItem->toArray());

        $orderItem = OrderItem::factory()->inFuture()->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->deleteJson(route('order_items.destroy', $orderItem))
            ->assertSuccessful();

        $this->assertDatabaseMissing('order_items', $orderItem->toArray());
    }

    /** @test */
    public function it_disallows_to_delete_an_order_item_in_the_past()
    {
        $user = User::factory()->create();
        /** @var OrderItem $orderItem */
        $orderItem = OrderItem::factory()->inPast()->make();
        $user->orderItems()->save($orderItem);

        $this->withExceptionHandling();

        $this->login($user)
            ->delete(route('order_items.destroy', $orderItem))
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', $orderItem->only(['order_id', 'user_id', 'meal_id', 'quantity']));

        $orderItem = OrderItem::factory()->inPast()->make();
        $user->orderItems()->save($orderItem);

        $this->login($user)
            ->deleteJson(route('order_items.destroy', $orderItem))
            ->assertForbidden();

        $this->assertDatabaseHas('order_items', $orderItem->only(['order_id', 'user_id', 'meal_id', 'quantity']));
    }

    /** @test */
    public function it_shows_a_delete_order_button_for_a_ordered_meal()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->login($user);

        $this->get(route('meals.index'))
            ->assertDontSee(__('Delete order'));

        $meal->order($user->id, $meal->date_from);

        $this->get(route('meals.index', ['date' => $meal->date_from->toDateString()]))
            ->assertSee(__('Delete order'));
    }


    /** @test */
    public function it_shows_a_delete_order_button_for_a_ordered_meal_variant()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->inFuture()->make()
        );


        /** @var User $user */
        $user = User::factory()->create();

        $this->login($user);

        $this->get(route('meals.index'))
            ->assertDontSee(__('Delete order'));

        $variant->order($user->id, $meal->date_from);

        $this->get(route('meals.index', ['date' => $variant->date_from->toDateString()]))
            ->assertSee(__('Delete order'));
    }

    /** @test */
    public function it_only_allows_to_order_a_meal_variant_in_meal_with_variants()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->inFuture()->make()
        );

        $this->login();

        $this->post(route('order_items.store'), [
            'date' => $variant->date_from->toDateString(),
            'meal_id' => $variant->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $variant->id)->exists());

        $this
            ->withExceptionHandling()
            ->post(route('order_items.store'), [
                'date' => $meal->date_from->toDateString(),
                'meal_id' => $meal->id
            ])
            ->assertSessionHasErrors('meal_id');

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    /** @test */
    public function admin_can_see_only_meal_variants_in_order_item_create_form()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make([
                    'date_from' => $meal->date_from,
                    'date_to' => $meal->date_to
                ]
            )
        );

        $this->loginAsAdmin();

        $this->get(route('order_items.create', [
            'date' => $meal->date_from->toDateString(),
        ]))
        ->assertViewHas('meals', function (Collection $meals) use ($meal, $variant) {
            return $meals->contains($variant) && !$meals->contains($meal);
        });
    }

    /** @test */
    public function admin_can_see_only_meal_variants_in_order_item_edit_form()
    {
        $user = User::factory()->create();

        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make([
                    'date_from' => $meal->date_from,
                    'date_to' => $meal->date_to
                ]
            )
        );

        $orderItem = $variant->order($user->id, $variant->date_from);
        $this->loginAsAdmin();

        $this->get(route('order_items.edit', $orderItem))
            ->assertViewHas('meals', function (Collection $meals) use ($meal, $variant) {
                return $meals->contains($variant) && !$meals->contains($meal);
            });
    }
}
