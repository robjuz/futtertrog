<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Livewire\Livewire;
use Tests\TestCase;

class MealOrderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_order_a_meal_for_himself()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        $this->login();
        $this->post(route('order_items.store'), [
            'meal_id' => $meal->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());

        $this->postJson(route('order_items.store'), [
            'meal_id' => $meal->id
        ])->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_order_a_meal_for_other_users()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->login()->withExceptionHandling()
            ->post(route('order_items.store'), [
                'user_id' => $user->id,
                'meal_id' => $meal->id
            ])->assertSessionHasErrors('user_id');

        $this->assertFalse($user->orderItems()->where('meal_id', $meal->id)->exists());
        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_order_a_meal_located_in_the_past()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        $this->login()
            ->withExceptionHandling()
            ->post(route('order_items.store'), [
                'meal_id' => $meal->id
            ])
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_order_a_meal_for_other_users()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->loginAsAdmin();

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());

        Livewire::test(ManageOrderItems::class)
            ->callAction('orderFor', data: [
                'user_id' => $user->id,
                'meal_id' => $meal->id,
                'quantity' => 1,
            ])->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
        $this->assertTrue($user->orderItems()->where('meal_id', $meal->id)->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_cannot_order_meals()
    {
        $this->withExceptionHandling();

        $this->post(route('order_items.store'))->assertRedirect(route('login'));
        $this->postJson(route('order_items.store'))->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dispatches_an_event_when_an_order_was_reopened()
    {
        /** @var Meal $meal1 */
        $meal1 = Meal::factory(['date' => today()->addDay()])->create();

        /** @var Meal $meal2 */
        $meal2 = Meal::factory([
            'date' => today()->addDay(),
            'provider' => $meal1->provider
        ])->create();

        /** @var User $user */
        $user = User::factory()->create();

        $orderItem = $user->order($meal1);

        // Given we have a closed order
        /** @var Order $order */
        $order = $orderItem->order;
        $order->markOrdered();

        Event::fake();

        // When a user creates a new order item associated with this order
        $this->login($user);

        $this->post(route('order_items.store'), [
            'meal_id' => $meal2->id
        ]);

        // En event is dispatched
        Event::assertDispatched(OrderUpdated::class, function ($event) use ($order, $user, $meal2) {
            $orderItem = $order->orderItems()->whereMealId($meal2->id)->first();
            return $event->order->is($order)
                && $event->user->is($user)
                && $event->orderItem->is($orderItem);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_its_status()
    {
        /** @var Meal $meal1 */
        $meal1 = Meal::factory(['date' => today()->addDay()])->create();

        /** @var Meal $meal2 */
        $meal2 = Meal::factory([
            'date' => today()->addDay(),
            'provider' => $meal1->provider
        ])->create();

        /** @var User $user */
        $user = User::factory()->create();

        $orderItem = $user->order($meal1);
        $order = $orderItem->order;

        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        $order->markOrdered();
        $this->assertEquals(Order::STATUS_ORDERED, $order->status);

        $user->order($meal2);
        $order->refresh();
        $this->assertEquals(Order::STATUS_OPEN, $order->status);

        $order->markOrdered();
        $order->refresh();
        $this->assertEquals(Order::STATUS_ORDERED, $order->status);

        $this->login($user)->putJson(route('order_items.update', $orderItem), ['quantity' => 2]);
        $order->refresh();
        $this->assertEquals(Order::STATUS_OPEN, $order->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_provides_a_list_of_order_items()
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->make();
        $user->orderItems()->save($orderItem);

        $orderItem->load('meal');

        $this->login($user)
            ->get(route('order_items.index'))
            ->assertJsonPath('items.0.id', $orderItem->id)
            ->assertJsonPath('items.0.title', $orderItem->meal->title)
            ->assertJsonPath('items.0.quantity', $orderItem->quantity)
            ->assertJsonPath('items.0.date', $orderItem->date->toDateString())
            ->assertJsonPath('items.0.price', (string) $orderItem->meal->price)
            ->assertJsonPath('items.0.subtotal', (string) $orderItem->subtotal)
            ->assertJsonMissingPath('items.0.user')
            ->assertJsonMissingPath('items.0.order');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_all_order_items()
    {
        $orderItem = OrderItem::factory()->create();

        $this->loginAsAdmin();

        Livewire::test(ManageOrderItems::class)->assertCanSeeTableRecords([$orderItem]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_order_items_from_other_users()
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->make();
        $user->orderItems()->save($orderItem);

        $orderItem2 = OrderItem::factory()->create();

        $orderItem->load('meal');

        $this->loginAsAdmin();

        Livewire::test(ManageOrderItems::class)->filterTable('user_id', $user->id)
            ->assertCanSeeTableRecords([$orderItem])
            ->assertCanNotSeeTableRecords([$orderItem2]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_a_delete_order_button_for_a_ordered_meal()
    {
        config(['user-interface.blade' => true]);
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $this->login($user);

        $this->get(route('meals.index'))
            ->assertDontSee(__('Delete order'));

        $meal->order($user->id);

        $this->get(route('meals.index', ['date' => $meal->date->toDateString()]))
            ->assertSee(__('Delete order'));
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_a_delete_order_button_for_a_ordered_meal_variant()
    {
        config(['user-interface.blade' => true]);
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make(['date' => $meal->date])
        );


        /** @var User $user */
        $user = User::factory()->create();

        $this->login($user);

        $this->get(route('meals.index'))
            ->assertDontSee(__('Delete order'));

        $user->order($variant);

        $this->get(route('meals.index', ['date' => $variant->date->toDateString()]))
            ->assertSee(__('Delete order'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
            'date' => $variant->date->toDateString(),
            'meal_id' => $variant->id
        ]);

        $this->assertTrue(auth()->user()->orderItems()->where('meal_id', $variant->id)->exists());

        $this
            ->withExceptionHandling()
            ->post(route('order_items.store'), [
                'meal_id' => $meal->id
            ])
            ->assertSessionHasErrors('meal_id');

        $this->assertFalse(auth()->user()->orderItems()->where('meal_id', $meal->id)->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_only_meal_variants_in_order_item_create_form()
    {
        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make([
                    'date' => $meal->date,
                ]
            )
        );

        $this->loginAsAdmin();

        Livewire::test(ManageOrderItems::class)->mountAction('orderFor')
            ->assertFormFieldExists('meal_id', function (Select $field) use ($meal, $variant): bool {
                $options = $field->getOptions();

                return array_key_exists($variant->id, $options) && !array_key_exists($meal->id, $options);
            });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_only_meal_variants_in_order_item_edit_form()
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Meal $meal */
        $meal = Meal::factory()->inFuture()->create();

        /** @var Meal $variant */
        $variant = $meal->variants()->save(
            Meal::factory()->make([
                    'date' => $meal->date,
                ]
            )
        );

        $orderItem = $user->order($variant);
        $this->loginAsAdmin();

        Livewire::test(ManageOrderItems::class)->mountTableAction('change', $orderItem)
            ->assertFormFieldExists('meal_id', function (Select $field) use ($meal, $variant): bool {
                $options = $field->getOptions();

                return array_key_exists($variant->id, $options) && !array_key_exists($meal->id, $options);
            });
    }
}
