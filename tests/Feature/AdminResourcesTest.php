<?php

namespace Tests\Feature;

use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use App\Actions\Admin\{Bookings, OrderItems, Orders};
use App\Filament\Pages\Notifications;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Meals\Pages\ManageMeals;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\MealProviders\Holzke;
use App\Models\{AdminActivity, CredentialChallenge, Deposit, Meal, Order, OrderItem};
use App\Notifications\CustomNotification;
use App\Mail\CredentialConfirmation;
use Illuminate\Support\Facades\{DB, Event, Http, Mail, Notification};
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminResourcesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
    }

    #[Test]
    public function panel_booking_uses_integer_cents_and_records_the_actor(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();

        Livewire::test(ManageDeposits::class)->callAction('book', data: [
            'user_id' => $recipient->id, 'value' => 1234, 'comment' => 'Panel',
        ])->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');

        $this->assertSame(1234, Deposit::sole()->value);
        $this->assertDatabaseHas('admin_activities', ['actor_id' => auth()->id(), 'action' => 'deposit.created']);
    }

    #[Test]
    public function booking_retries_reuse_the_operation_key_but_a_new_dialog_allows_the_same_booking(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();
        $data = ['user_id' => $recipient->id, 'value' => 1234, 'operation_key' => 'e64a325a-8611-4836-bce6-48fd3bed124b'];
        $page = Livewire::test(ManageDeposits::class);

        $page->callAction('book', data: $data)->assertNotified('Vorgang erfolgreich.');
        $page->callAction('book', data: $data)->assertNotified('Vorgang erfolgreich.');
        $this->assertDatabaseCount('deposits', 1);
        $page->callAction('book', data: [...$data, 'value' => 999])->assertNotified('Vorgang nicht ausgeführt.');
        $this->assertSame(1234, Deposit::sole()->value);
        $page->unmountAction()->callAction('book', data: ['user_id' => $recipient->id, 'value' => 1234]);

        $this->assertDatabaseCount('deposits', 2);
        $this->assertDatabaseCount('admin_activities', 2);
    }

    #[Test]
    public function transfers_book_both_legs_once_and_a_new_operation_can_repeat_them(): void
    {
        $this->loginAsAdmin();
        $source = User::factory()->create();
        $target = User::factory()->create();
        $data = ['source' => $source->id, 'target' => $target->id, 'value' => 1050,
            'operation_key' => 'e64a325a-8611-4836-bce6-48fd3bed124b'];
        $page = Livewire::test(ManageDeposits::class);

        $page->callAction('transfer', data: $data)->assertNotified('Vorgang erfolgreich.');
        $page->callAction('transfer', data: $data)->assertNotified('Vorgang erfolgreich.');

        $this->assertSame([-1050, 1050], Deposit::orderBy('id')->pluck('value')->all());
        $this->assertDatabaseCount('admin_activities', 1);
        $page->callAction('transfer', data: ['source' => $source->id, 'target' => $target->id, 'value' => 1050]);
        $this->assertDatabaseCount('deposits', 4);
    }

    #[Test]
    public function bookings_can_be_changed_and_deleted_through_the_same_actions(): void
    {
        $actor = User::factory()->admin()->create();
        $this->login($actor);
        $recipient = User::factory()->create();
        $deposit = app(Bookings::class)->create($actor, ['user_id' => $recipient->id, 'value' => 100]);

        Livewire::test(ManageDeposits::class)->callTableAction('change', $deposit, data: [
            'user_id' => $recipient->id, 'value' => -1234, 'comment' => 'Korrektur',
        ])->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $this->assertSame(-1234, $deposit->fresh()->value);
        Livewire::test(ManageDeposits::class)->callTableAction('remove', $deposit)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($deposit);
        $this->assertSame(['deposit.created', 'deposit.updated', 'deposit.deleted'], AdminActivity::orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function invalid_financial_input_is_explained_without_booking(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();

        Livewire::test(ManageDeposits::class)->callAction('book', data: ['user_id' => $recipient->id, 'value' => '1.25'])
            ->assertHasActionErrors(['value' => 'integer']);
        Livewire::test(ManageDeposits::class)->callAction('transfer', data: ['source' => $recipient->id, 'target' => 99999, 'value' => 100])
            ->assertHasActionErrors(['target']);

        $this->assertDatabaseCount('deposits', 0);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    public function a_failed_audit_rolls_back_the_panel_booking_and_shows_an_error(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();
        DB::statement("CREATE TRIGGER reject_panel_audit BEFORE INSERT ON admin_activities BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");

        Livewire::test(ManageDeposits::class)->callAction('book', data: ['user_id' => $recipient->id, 'value' => 1234])
            ->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertDatabaseCount('deposits', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        DB::statement('DROP TRIGGER reject_panel_audit');
    }

    #[Test]
    public function users_can_be_searched_filtered_paginated_and_restored_without_exposing_credentials(): void
    {
        $this->loginAsAdmin();
        $target = User::factory()->create(['name' => 'Gesuchter Benutzer']);
        $disabled = User::factory()->create(['deleted_at' => now()]);
        User::factory()->count(11)->create();

        $page = Livewire::test(ManageUsers::class)->searchTable('Gesuchter')->assertCanSeeTableRecords([$target])->assertCanNotSeeTableRecords([$disabled]);
        $this->assertStringNotContainsString($target->password, $page->html());
        $this->assertStringNotContainsString($target->remember_token, $page->html());
        $page->searchTable('')->filterTable('is_admin', true)->assertCanNotSeeTableRecords([$target]);
        $page->resetTableFilters()->filterTable('trashed', false)->assertCanSeeTableRecords([$disabled]);
        $page->callTableAction('restore', $disabled)->assertNotified('Vorgang erfolgreich.');
        $this->assertNull($disabled->fresh()->deleted_at);
        $page->resetTableFilters()->set('tableRecordsPerPage', 10)->assertCountTableRecords(14)->call('gotoPage', 2)->assertSet('paginators.page', 2);
    }

    #[Test]
    public function role_changes_and_deactivation_are_logged_and_self_deactivation_is_explained(): void
    {
        $actor = User::factory()->admin()->create();
        $this->login($actor);
        $target = User::factory()->create();
        $page = Livewire::test(ManageUsers::class);

        $page->callTableAction('role', $target, data: ['is_admin' => true])->assertNotified('Vorgang erfolgreich.');
        $this->assertTrue((bool) $target->fresh()->is_admin);
        $page->callTableAction('deactivate', $target)->assertNotified('Vorgang erfolgreich.');
        $this->assertSoftDeleted($target);
        $page->callTableAction('deactivate', $actor)->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertNotSoftDeleted($actor);
        $this->assertDatabaseHas('admin_activities', ['actor_id' => $actor->id, 'action' => 'user.updated']);
        $this->assertDatabaseHas('admin_activities', ['actor_id' => $actor->id, 'action' => 'user.deactivated']);
    }

    #[Test]
    public function the_last_active_administrator_cannot_be_demoted(): void
    {
        $actor = User::factory()->admin()->create();
        $this->login($actor);

        Livewire::test(ManageUsers::class)->callTableAction('role', $actor, data: ['is_admin' => false])
            ->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertTrue((bool) $actor->fresh()->is_admin);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    public function password_confirmation_uses_the_existing_challenge_and_never_accepts_a_password(): void
    {
        $this->loginAsAdmin();
        $target = User::factory()->disabled()->create();
        $hash = $target->password;
        Mail::fake();

        $page = Livewire::test(ManageUsers::class)->callTableAction('passwordLink', $target)->assertNotified('Vorgang erfolgreich.');

        Mail::assertSent(CredentialConfirmation::class);
        $this->assertDatabaseHas('credential_challenges', ['user_id' => $target->id, 'purpose' => CredentialChallenge::ResetPassword]);
        $this->assertSame($hash, $target->fresh()->password);
        $this->assertStringNotContainsString('token_hash', $page->html());
        $this->assertStringNotContainsString($hash, $page->html());
    }

    #[Test]
    public function meals_and_variants_can_be_created_changed_searched_and_deleted(): void
    {
        $this->loginAsAdmin();
        $page = Livewire::test(ManageMeals::class);
        $data = ['title' => 'Hauptgericht', 'price' => 1234, 'date' => today()->addDay()->toDateString(), 'description' => 'Frisch'];

        $page->callAction('add', data: $data)->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $parent = Meal::sole();
        $this->assertSame('1234', $parent->price->getAmount());
        $page->callAction('add', data: [...$data, 'parent_id' => $parent->id, 'title' => 'Vegetarisch'])->assertNotified('Vorgang erfolgreich.');
        $variant = Meal::whereNotNull('parent_id')->sole();
        $page->callTableAction('change', $variant, data: [...$data, 'parent_id' => $parent->id, 'title' => 'Vegan', 'price' => 999])->assertHasNoActionErrors();
        $this->assertSame('Hauptgericht Vegan', $variant->fresh()->title);
        $this->assertSame('999', $variant->fresh()->price->getAmount());
        $page->searchTable('Vegan')->assertCanSeeTableRecords([$variant])->assertCanNotSeeTableRecords([$parent]);
        $page->searchTable('')->callTableAction('remove', $parent)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($parent);
        $this->assertModelMissing($variant);
    }

    #[Test]
    public function ordered_meals_or_variants_cannot_be_deleted_and_invalid_meals_are_explained(): void
    {
        $this->loginAsAdmin();
        $parent = Meal::factory()->create();
        $variant = Meal::factory()->create(['parent_id' => $parent->id]);
        OrderItem::factory()->create(['meal_id' => $variant->id]);

        Livewire::test(ManageMeals::class)->callTableAction('remove', $parent)->assertNotified('Vorgang nicht ausgeführt.');
        Livewire::test(ManageMeals::class)->callAction('add', data: ['title' => '', 'date' => 'invalid', 'price' => -100])
            ->assertHasActionErrors(['title' => 'required', 'date' => 'date', 'price' => 'min']);

        $this->assertModelExists($parent);
        $this->assertModelExists($variant);
    }

    #[Test]
    public function imports_use_the_fake_provider_and_record_the_summary_without_secrets(): void
    {
        $this->loginAsAdmin();
        Http::preventStrayRequests();
        config(['services.holzke.enabled' => true]);
        $this->app->register(\App\Providers\MealProvidersServiceProvider::class, true);
        $provider = $this->partialMock(Holzke::class);
        $provider->shouldReceive('createMealsDataForDate')->once()->andReturn(3);
        $provider->shouldReceive('notifyAboutNewOrderPossibilities')->once();

        Livewire::test(ManageMeals::class)->callAction('import', data: ['provider' => 'Holzke', 'date' => '2026-11-19', 'notify' => true])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');

        $this->assertSame(['provider' => 'Holzke', 'date' => '2026-11-19', 'count' => 3], AdminActivity::sole()->changes['after']);
        Http::assertNothingSent();
    }

    #[Test]
    public function failed_imports_display_an_error_and_do_not_record_success(): void
    {
        $this->loginAsAdmin();
        Http::preventStrayRequests();
        config(['services.holzke.enabled' => true]);
        $this->app->register(\App\Providers\MealProvidersServiceProvider::class, true);
        $this->partialMock(Holzke::class)->shouldReceive('createMealsDataForDate')->once()->andThrow(new RuntimeException('private-provider-token'));

        $page = Livewire::test(ManageMeals::class)->callAction('import', data: ['provider' => 'Holzke', 'date' => '2026-11-19'])->assertNotified('Vorgang nicht ausgeführt.');

        $this->assertStringNotContainsString('private-provider-token', $page->html());
        $this->assertDatabaseCount('admin_activities', 0);
        Http::assertNothingSent();
    }

    #[Test]
    public function proxy_orders_and_deadline_exceptions_have_identical_quantities_totals_and_status(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();
        $meal = Meal::factory()->inPast()->create(['price' => 1234]);
        Event::fake([\App\Events\OrderUpdated::class]);

        Livewire::test(ManageOrderItems::class)->callAction('orderFor', data: ['meal_id' => $meal->id, 'user_id' => $recipient->id, 'quantity' => 2])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $item = OrderItem::sole();
        $this->assertSame(2, $item->quantity);
        $this->assertSame('2468', $item->subtotal->getAmount());
        Livewire::test(ManageOrderItems::class)->callTableAction('change', $item, data: ['meal_id' => $meal->id, 'user_id' => $recipient->id, 'quantity' => 3])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $this->assertSame('3702', $item->fresh()->subtotal->getAmount());
        $this->assertSame(Order::STATUS_OPEN, $item->order->fresh()->status);
        Livewire::test(ManageOrderItems::class)->callTableAction('remove', $item)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($item);
        Event::assertDispatchedTimes(\App\Events\OrderUpdated::class, 3);
        $this->assertSame(['order_item.created', 'order_item.updated', 'order_item.deleted'], AdminActivity::orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function orders_can_be_closed_reopened_paid_and_deleted_with_audit(): void
    {
        $this->loginAsAdmin();
        $item = OrderItem::factory()->create();
        $order = $item->order;
        $payer = $item->user;
        $page = Livewire::test(ManageOrders::class);

        $page->callTableAction('change', $order, data: ['status' => 'ordered', 'payed_at' => '2026-11-18', 'user_id' => $payer->id])->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertTrue($order->fresh()->is_payed);
        $page->callTableAction('change', $order, data: ['status' => 'open', 'payed_at' => null, 'user_id' => null])->assertHasNoActionErrors();
        $this->assertFalse($order->fresh()->is_payed);
        $page->callTableAction('remove', $order)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($order);
        $this->assertSame(['order.updated', 'order.updated', 'order.deleted'], AdminActivity::orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function invalid_order_transitions_and_quantities_are_explained_without_changes(): void
    {
        $this->loginAsAdmin();
        $item = OrderItem::factory()->create(['quantity' => 2]);

        Livewire::test(ManageOrders::class)->callTableAction('change', $item->order, data: ['status' => 'invalid'])->assertHasActionErrors(['status']);
        Livewire::test(ManageOrderItems::class)->callTableAction('change', $item, data: ['quantity' => 11])->assertHasActionErrors(['quantity' => 'max']);

        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    public function notifications_send_to_selected_recipients_and_only_log_the_recipient_ids(): void
    {
        $this->loginAsAdmin();
        $recipients = User::factory()->count(2)->create();
        Notification::fake();

        Livewire::test(Notifications::class)->callAction('send', data: ['user_id' => $recipients->modelKeys(), 'subject' => 'Betreff', 'body' => 'Vertraulicher Nachrichtentext'])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');

        Notification::assertSentTo($recipients, CustomNotification::class);
        $this->assertSame(['recipient_ids' => $recipients->modelKeys()], AdminActivity::sole()->changes['after']);
        $this->assertStringNotContainsString('Nachrichtentext', AdminActivity::sole()->toJson());
    }

    #[Test]
    public function invalid_notifications_show_errors_and_send_nothing(): void
    {
        $this->loginAsAdmin();
        Notification::fake();

        Livewire::test(Notifications::class)->callAction('send', data: ['user_id' => [], 'subject' => '', 'body' => ''])->assertHasActionErrors(['user_id', 'subject', 'body']);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('admin_activities', 0);
    }
    #[Test]
    public function resubmitting_the_same_signed_booking_dialog_does_not_create_a_second_booking(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create();
        $page = Livewire::test(ManageDeposits::class)->mountAction('book')->fillForm(['user_id' => $recipient->id, 'value' => 1234]);
        $payload = ['components' => [['snapshot' => json_encode($page->snapshot), 'updates' => [], 'calls' => [['method' => 'callMountedAction', 'params' => []]]]]];

        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $payload)->assertOk();
        $this->postJson(Livewire::getUpdateUri(), $payload)->assertOk();

        $this->assertDatabaseCount('deposits', 1);
        $this->assertSame(1234, Deposit::sole()->value);
        $this->assertDatabaseCount('admin_activities', 1);
    }

    #[Test]
    public function manual_meal_notifications_keep_the_authorized_legacy_path_until_an_admin_action_exists(): void
    {
        $this->loginAsAdmin();
        $preferences = new \App\UserSettings();
        $preferences->newOrderPossibilityNotification = true;
        $recipient = User::factory()->create(['settings' => $preferences]);
        $other = User::factory()->create();
        Notification::fake();

        $this->get(route('meals.create'))->assertOk()->assertSee('notify');
        $this->post(route('meals.store'), [
            'title' => 'Neues Gericht', 'price' => 1234, 'date' => today()->addDay()->toDateString(), 'notify' => true,
        ])->assertRedirect(route('admin.meals.index'));

        Notification::assertSentTo($recipient, \App\Notifications\NewOrderPossibility::class);
        Notification::assertNotSentTo($other, \App\Notifications\NewOrderPossibility::class);
        $this->assertDatabaseCount('meals', 1);
    }

    #[Test]
    public function a_deactivated_recipient_cannot_receive_a_new_proxy_order(): void
    {
        $this->loginAsAdmin();
        $recipient = User::factory()->create(['deleted_at' => now()]);
        $meal = Meal::factory()->create();

        Livewire::test(ManageOrderItems::class)->callAction('orderFor', data: [
            'meal_id' => $meal->id, 'user_id' => $recipient->id, 'quantity' => 3,
        ])->assertHasActionErrors(['user_id']);

        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('admin_activities', 0);
    }
}
