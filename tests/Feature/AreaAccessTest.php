<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\MealProviders\Holzke;
use App\Providers\MealProvidersServiceProvider;
use Illuminate\Support\Facades\Http;
use App\Models\Deposit;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AreaAccessTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function administrativeRequests(): iterable
    {
        foreach ([
            ['GET', 'users'], ['GET', 'users/1'], ['GET', 'users/1/edit'],
            ['GET', 'users/create'], ['POST', 'users'], ['PUT', 'users/1'],
            ['DELETE', 'users/1'], ['POST', 'users/1/restore'],
            ['GET', 'meals/create'], ['GET', 'meals/1/edit'], ['POST', 'meals'],
            ['PUT', 'meals/1'], ['DELETE', 'meals/1'], ['POST', 'meals/import'],
            ['GET', 'orders'], ['GET', 'orders/1/edit'], ['PUT', 'orders/1'],
            ['DELETE', 'orders/1'], ['POST', 'orders/1/auto_order'],
            ['GET', 'deposits'], ['GET', 'deposits/create'], ['GET', 'deposits/1/edit'],
            ['POST', 'deposits'], ['PUT', 'deposits/1'], ['DELETE', 'deposits/1'],
            ['GET', 'deposits/transfer'], ['POST', 'deposits/transfer'],
            ['GET', 'notifications/create'], ['POST', 'notifications'],
            ['GET', 'order_items/create'], ['GET', 'order_items/1/edit'],
        ] as [$method, $path]) {
            foreach ([false, true] as $adminPath) {
                $url = ($adminPath ? '/admin/' : '/').$path;
                yield "$method $url guest" => [$method, $url, false];
                yield "$method $url user" => [$method, $url, true];
            }
        }
        foreach ([['GET', '/admin'], ['GET', '/admin/meals'], ['GET', '/admin/order_items'],
            ['POST', '/admin/order_items'], ['PUT', '/admin/order_items/1'],
            ['DELETE', '/admin/order_items/1'], ['POST', '/order_items/json']] as [$method, $url]) {
            yield "$method $url guest" => [$method, $url, false];
            yield "$method $url user" => [$method, $url, true];
        }
    }

    #[Test]
    #[DataProvider('administrativeRequests')]
    public function direct_administrative_requests_do_not_disclose_or_change_data(string $method, string $url, bool $authenticated): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $item = OrderItem::factory()->create(['user_id' => $user->id, 'quantity' => 1]);
        $deposit = Deposit::factory()->create(['user_id' => $user->id, 'value' => 1234]);
        $item->order->update(['status' => Order::STATUS_ORDERED]);
        Mail::fake();
        Notification::fake();
        Event::fake([OrderUpdated::class]);
        $this->mock(Holzke::class)->shouldNotReceive('placeOrder', 'updateOrder', 'createMealsDataForDate');
        if ($authenticated) {
            $this->login();
        }
        $this->withExceptionHandling();

        $response = $this->json($method, $url, [
            'name' => 'Unauthorized', 'user_id' => $user->id, 'meal_id' => $item->meal_id,
            'quantity' => 9, 'value' => 5, 'source' => $user->id, 'target' => $user->id,
            'date' => today()->toDateString(), 'provider' => 'Holzke',
            'subject' => 'Unauthorized', 'body' => 'Unauthorized',
        ]);

        $this->assertContains($response->status(), $authenticated ? [403, 404, 405] : [401, 404, 405]);
        $response->assertDontSee($user->email)->assertDontSee($item->meal->title);
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(Order::STATUS_ORDERED, $item->order->fresh()->status);
        $this->assertSame(1234, $deposit->fresh()->value);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('meals', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertNotSoftDeleted($user);
        $this->assertModelExists($item->meal);
        Notification::assertNothingSent();
        Mail::assertNothingSent();
        Event::assertNotDispatched(OrderUpdated::class);
    }

    #[Test]
    public function active_administrator_has_separate_navigation_and_authorized_legacy_redirects(): void
    {
        $item = OrderItem::factory()->create();
        $deposit = Deposit::factory()->create();
        $this->loginAsAdmin();

        $this->get('/admin')->assertOk()->assertSee('Administration')->assertSee('/admin/users', false);
        foreach (['users', 'users/create', "users/{$item->user_id}", "users/{$item->user_id}/edit",
            'meals/create', "meals/{$item->meal_id}/edit", 'orders', "orders/{$item->order_id}/edit",
            'deposits', 'deposits/create', "deposits/{$deposit->id}/edit", 'deposits/transfer',
            'notifications/create', 'order_items/create', "order_items/{$item->id}/edit"] as $path) {
            $this->get('/'.$path)->assertRedirect('/admin/'.$path);
            $this->get('/admin/'.$path)->assertOk()->assertSee('admin-navigation', false);
        }
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function deactivated_accounts_cannot_enter_either_area(bool $admin): void
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $meal = Meal::factory()->inFuture()->create();
        $this->login($user);
        User::whereKey($user->id)->delete();
        $this->withExceptionHandling();

        foreach (['/', '/admin', '/admin/users', '/my/orders', '/my/balance', '/settings', '/meals/ical'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->postJson('/order_items', ['meal_id' => $meal->id])->assertForbidden();
        $item = OrderItem::factory()->inFuture()->create(['quantity' => 1]);
        $deposit = Deposit::factory()->create(['value' => 1234]);
        Notification::fake();
        $this->mock(Holzke::class)->shouldNotReceive('placeOrder', 'updateOrder', 'createMealsDataForDate');
        foreach ([['PUT', '/admin/order_items/'.$item->id], ['POST', '/admin/meals/import'],
            ['POST', '/admin/deposits/transfer'], ['POST', '/admin/orders/'.$item->order_id.'/auto_order'],
            ['POST', '/admin/notifications'], ['PUT', '/admin/users/'.$item->user_id]] as [$method, $url]) {
            $this->json($method, $url, ['quantity' => 9, 'name' => 'Forbidden'])->assertForbidden();
        }
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(1234, $deposit->fresh()->value);
        Notification::assertNothingSent();
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function own_order_uses_session_identity_and_can_be_changed_within_deadline(bool $admin): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_admin' => $admin]);
        $meal = Meal::factory()->inFuture()->create();
        Event::fake([OrderUpdated::class]);
        $this->login($user);

        $this->postJson('/order_items', ['meal_id' => $meal->id, 'quantity' => 2])->assertCreated();
        $item = OrderItem::sole();
        $this->assertSame($user->id, $item->user_id);
        $this->putJson('/order_items/'.$item->id, ['quantity' => 3])->assertOk();
        $this->assertSame(3, $item->fresh()->quantity);
        $this->deleteJson('/order_items/'.$item->id)->assertNoContent();
        $this->assertModelMissing($item);
        Event::assertDispatchedTimes(OrderUpdated::class, 3);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function self_service_rejects_foreign_objects_and_expired_orders_even_for_admins(bool $admin): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_admin' => $admin]);
        $foreign = OrderItem::factory()->inFuture()->create(['quantity' => 1]);
        $expired = OrderItem::factory()->create(['user_id' => $user->id, 'quantity' => 1]);
        Event::fake([OrderUpdated::class]);
        $this->login($user)->withExceptionHandling();

        foreach ([$foreign, $expired] as $item) {
            $this->putJson('/order_items/'.$item->id, ['quantity' => 9])->assertForbidden();
            $this->deleteJson('/order_items/'.$item->id)->assertForbidden();
            $this->assertSame(1, $item->fresh()->quantity);
        }
        $this->postJson('/order_items', ['meal_id' => $expired->meal_id])->assertForbidden();
        $own = OrderItem::factory()->inFuture()->create(['user_id' => $user->id, 'quantity' => 1]);
        $this->putJson('/order_items/'.$own->id, ['meal_id' => $expired->meal_id])->assertForbidden();
        $this->assertSame($own->meal_id, $own->fresh()->meal_id);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function self_service_rejects_identity_and_administrative_fields(bool $admin): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_admin' => $admin]);
        $other = User::factory()->create();
        $item = OrderItem::factory()->inFuture()->create(['user_id' => $user->id, 'quantity' => 1]);
        Event::fake([OrderUpdated::class]);
        $this->login($user)->withExceptionHandling();

        foreach (['user_id' => $other->id, 'is_admin' => !$admin, 'order_id' => 999, 'status' => 'ordered', 'payed_at' => '2026-01-01'] as $field => $value) {
            $payload = [$field => $value, 'meal_id' => $item->meal_id, 'quantity' => 9];
            $this->postJson('/order_items', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->putJson('/order_items/'.$item->id, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->postJson('/settings', [$field => $value])->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->postJson('/notifications/disable', [$field => $value])->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        foreach (['/', '/my/orders', '/my/balance', '/meals', '/meals/ical', '/settings', '/order_items'] as $url) {
            $this->getJson($url.'?user_id='.$other->id)->assertUnprocessable()->assertJsonValidationErrors('user_id');
        }
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame($admin, $user->fresh()->is_admin);
        $this->assertDatabaseCount('disabled_notifications', 0);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    #[Test]
    public function administrative_order_actions_preserve_proxy_and_deadline_exceptions(): void
    {
        $meal = Meal::factory()->inPast()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();
        Event::fake([OrderUpdated::class]);
        $this->loginAsAdmin();

        $this->postJson('/admin/order_items', ['meal_id' => $meal->id, 'user_id' => $user->id])->assertCreated();
        $item = OrderItem::sole();
        $this->assertSame($user->id, $item->user_id);
        $this->putJson('/admin/order_items/'.$item->id, ['user_id' => $other->id, 'quantity' => 2])->assertOk();
        $this->assertSame($other->id, $item->fresh()->user_id);
        $this->assertSame(2, $item->fresh()->quantity);
        $this->deleteJson('/admin/order_items/'.$item->id)->assertNoContent();
        $this->assertModelMissing($item);
        Event::assertDispatchedTimes(OrderUpdated::class, 3);
    }

    #[Test]
    public function normal_views_offer_an_admin_entry_without_management_controls_or_foreign_data(): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        $own = OrderItem::factory()->create(['user_id' => $admin->id,
            'meal_id' => Meal::factory()->inFuture()->create(['title' => 'Own future meal'])->id]);
        $foreign = OrderItem::factory()->create([
            'meal_id' => Meal::factory()->create(['title' => 'Foreign meal'])->id]);
        Deposit::factory()->create(['user_id' => $foreign->user_id, 'comment' => 'Foreign deposit']);
        $this->login($admin);

        foreach (['/', '/my/orders', '/my/balance', '/meals?date='.$own->date->toDateString(), '/settings'] as $url) {
            $this->get($url)->assertOk()->assertSee('href="'.url('/admin').'"', false)
                ->assertDontSee('name="user_id"', false)->assertDontSee('name="is_admin"', false)
                ->assertDontSee('href="'.url('/admin/meals/'.$own->meal_id.'/edit').'"', false)
                ->assertDontSee('admin-navigation', false)->assertDontSee($foreign->meal->title)
                ->assertDontSee('Foreign deposit')->assertDontSee(__('System balance'));
        }
        $this->getJson('/order_items')->assertOk()->assertJsonFragment(['id' => $own->id])->assertJsonMissing(['id' => $foreign->id]);
        $this->get('/my/orders')->assertSee($own->meal->title);
        $this->get('/meals/ical')->assertOk()->assertSee($own->meal->title)->assertDontSee($foreign->meal->title);
    }

    #[Test]
    public function get_logout_preserves_session_and_post_logout_requires_csrf(): void
    {
        $user = User::factory()->create();
        $this->login($user)->withExceptionHandling();
        $this->app['env'] = 'csrf-test';

        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertStatus(419);
        $this->assertAuthenticatedAs($user);
        $this->withSession(['_token' => 'logout-token'])->post('/logout', ['_token' => 'logout-token'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function user_restoration_is_authorized_before_it_changes_any_data(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $this->login()->withExceptionHandling();

        $this->postJson(route('users.restore', $user))->assertForbidden();

        $this->assertSoftDeleted($user);
    }

    #[Test]
    public function the_last_active_admin_cannot_be_demoted_and_admins_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = User::factory()->admin()->create();
        $inactive->delete();
        $this->login($admin)->withExceptionHandling();

        $this->putJson(route('users.update', $admin), ['is_admin' => false])->assertForbidden();
        $this->deleteJson(route('users.destroy', $admin))->assertForbidden();
        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertNotSoftDeleted($admin);
    }

    #[Test]
    public function an_admin_can_be_demoted_when_another_active_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $this->login($admin);

        $this->putJson(route('users.update', $admin), ['is_admin' => false])->assertOk();

        $this->assertFalse($admin->fresh()->is_admin);
    }
    #[Test]
    public function old_administrative_write_routes_are_closed_even_for_administrators(): void
    {
        $item = OrderItem::factory()->create(['quantity' => 1]);
        $deposit = Deposit::factory()->create(['value' => 1234]);
        Notification::fake();
        $this->loginAsAdmin()->withExceptionHandling();

        foreach ([['POST', '/users'], ['PUT', '/users/'.$item->user_id], ['DELETE', '/users/'.$item->user_id],
            ['POST', '/users/'.$item->user_id.'/restore'], ['POST', '/meals'], ['POST', '/meals/import'],
            ['PUT', '/meals/'.$item->meal_id], ['DELETE', '/meals/'.$item->meal_id],
            ['PUT', '/orders/'.$item->order_id], ['DELETE', '/orders/'.$item->order_id],
            ['POST', '/orders/'.$item->order_id.'/auto_order'], ['POST', '/deposits'],
            ['PUT', '/deposits/'.$deposit->id], ['DELETE', '/deposits/'.$deposit->id],
            ['POST', '/deposits/transfer'], ['POST', '/notifications'], ['POST', '/order_items/json']] as [$method, $url]) {
            $response = $this->json($method, $url, ['quantity' => 9, 'name' => 'Forbidden']);
            $this->assertContains($response->status(), [404, 405]);
        }
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(1234, $deposit->fresh()->value);
        $this->assertModelExists($item->meal);
        $this->assertModelExists($item->order);
        $this->assertNotSoftDeleted($item->user);
        Notification::assertNothingSent();
    }

    #[Test]
    public function guests_cannot_read_personal_pages_or_calendar(): void
    {
        $this->withExceptionHandling();

        foreach (['/', '/meals', '/my/orders', '/my/balance', '/settings', '/meals/ical'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
            $this->getJson($url)->assertUnauthorized();
        }
    }

    #[Test]
    public function admin_import_and_autoorder_only_invoke_faked_providers(): void
    {
        $this->freezeTime();
        Http::preventStrayRequests();
        config(['services.holzke.enabled' => true]);
        $this->app->register(MealProvidersServiceProvider::class, true);
        $meal = Meal::factory()->inFuture()->create(['provider' => 'Holzke', 'external_id' => 'meal-1']);
        $order = Order::factory()->create(['provider' => 'Holzke', 'status' => Order::STATUS_OPEN]);
        OrderItem::factory()->create(['meal_id' => $meal->id, 'order_id' => $order->id]);
        $provider = $this->partialMock(Holzke::class);
        $provider->shouldReceive('createMealsDataForDate')->once()->withArgs(fn ($date) => $date->isSameDay($meal->date))->andReturn(1);
        $provider->shouldReceive('placeOrder')->once()->withArgs(fn (Order $target) => $target->is($order))
            ->andReturnUsing(function (Order $target): void { $target->markOrdered(); });
        $this->loginAsAdmin();

        $this->post('/admin/meals/import', ['date' => $meal->date->toDateString(), 'provider' => 'Holzke'])->assertRedirect(route('meals.create'));
        $this->postJson('/admin/orders/'.$order->id.'/auto_order')->assertOk();

        $this->assertSame(Order::STATUS_ORDERED, $order->fresh()->status);
        Http::assertNothingSent();
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function the_existing_calendar_token_path_remains_scoped_to_its_authenticated_user(bool $admin): void
    {
        $user = User::factory()->create(['is_admin' => $admin]);
        $own = OrderItem::factory()->create(['user_id' => $user->id,
            'meal_id' => Meal::factory()->create(['title' => 'Own calendar meal'])->id]);
        $foreign = OrderItem::factory()->create([
            'meal_id' => Meal::factory()->create(['title' => 'Foreign calendar meal'])->id]);
        $this->withExceptionHandling();

        $this->get('/meals/ical?api_token='.$user->api_token)->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee($own->meal->title)->assertDontSee($foreign->meal->title);
        $this->getJson('/api/orders')->assertNotFound();
    }

}
