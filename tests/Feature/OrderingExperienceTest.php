<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Deposit;
use App\Models\ExternalIdentity;
use App\Models\CredentialChallenge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Events\OrderUpdated;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderingExperienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[Test]
    #[DataProvider('interfaces')]
    public function meal_status_queries_are_constant_with_variants_and_other_users_orders(bool $blade): void
    {
        config(['user-interface.blade' => $blade]);
        $this->travelTo(now()->setDate(2026, 11, 18)->startOfDay());
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->login($user);
        $counts = [];
        foreach ([1, 12] as $count) {
            for ($i = Meal::whereNull('parent_id')->count(); $i < $count; $i++) {
                $meal = Meal::factory()->create(['date' => today()->addDay()]);
                $variant = Meal::factory()->create(['parent_id' => $meal->id, 'date' => today()->addDay()]);
                OrderItem::factory()->create(['user_id' => $user->id, 'meal_id' => $variant->id]);
                OrderItem::factory()->create(['user_id' => $other->id, 'meal_id' => $variant->id]);
            }
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get(route('meals.index', ['date' => today()->addDay()->toDateString()]))->assertOk();
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        $this->assertSame($counts[0], $counts[1], 'Queries for 1/12 cards: '.implode('/', $counts));
    }

    #[Test]
    public function ordering_page_exposes_only_own_confirmed_variant_and_explicit_fields(): void
    {
        $user = User::factory()->create();
        $meal = Meal::factory()->inFuture()->create();
        $variant = Meal::factory()->create(['parent_id' => $meal->id, 'date' => $meal->date]);
        $item = $user->order($variant);
        $this->login($user)->get(route('meals.index', ['date' => $meal->date->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page->component('Meals/Index')
                ->where('meals.0.id', $meal->id)
                ->where('meals.0.orders.0.id', $item->id)
                ->where('meals.0.orders.0.meal_id', $variant->id)
                ->missing('meals.0.orders.0.user')
                ->missing('auth')->etc());
    }

    #[Test]
    public function confirmed_json_mutations_return_a_bounded_order_and_persist_variant_quantity_and_cancellation(): void
    {
        Event::fake([OrderUpdated::class]);
        $user = User::factory()->create();
        $meal = Meal::factory()->inFuture()->create();
        $small = Meal::factory()->create(['parent_id' => $meal->id, 'date' => $meal->date, 'title' => 'Klein']);
        $large = Meal::factory()->create(['parent_id' => $meal->id, 'date' => $meal->date, 'title' => 'Groß']);
        $this->login($user);
        $created = $this->postJson('/order_items', ['meal_id' => $small->id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('meal_id', $small->id)->assertJsonPath('quantity', 2)
            ->assertJsonMissingPath('user')->assertJsonMissingPath('order')->assertJsonMissingPath('created_at');
        $id = $created->json('id');
        $this->assertDatabaseHas('order_items', ['id' => $id, 'user_id' => $user->id, 'meal_id' => $small->id, 'quantity' => 2]);
        $this->putJson('/order_items/'.$id, ['meal_id' => $large->id, 'quantity' => 3])
            ->assertOk()->assertJsonPath('meal_id', $large->id)->assertJsonPath('quantity', 3);
        $this->assertDatabaseHas('order_items', ['id' => $id, 'user_id' => $user->id, 'meal_id' => $large->id, 'quantity' => 3]);
        $this->deleteJson('/order_items/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('order_items', ['id' => $id]);
        Event::assertDispatchedTimes(OrderUpdated::class, 3);
    }

    #[Test]
    public function day_provider_and_notification_status_are_shared_without_per_day_model_serializations(): void
    {
        $user = User::factory()->create();
        $meal = Meal::factory()->inFuture()->create();
        $user->order($meal);
        $this->login($user)->postJson('/notifications/disable', ['date' => $meal->date->toDateString()])->assertCreated();
        $this->get('/meals?date='.$meal->date->toDateString().'&provider=Internal')
            ->assertInertia(fn (Assert $page) => $page->component('Meals/Index')
                ->where('provider', 'Internal')->where('date', $meal->date->toDateString())
                ->where('notificationDisabled', true)->has('meals', 1)
                ->where('days', fn ($days): bool => collect($days)->contains(fn ($day): bool => $day['date'] === $meal->date->toDateString() && $day['ordered'] && $day['notificationDisabled'])));
        $this->deleteJson('/notifications/disable', ['date' => $meal->date->toDateString()])->assertNoContent();
        $this->assertDatabaseCount('disabled_notifications', 0);
    }

    #[Test]
    public function preferences_and_description_visibility_apply_to_vue_ordering_and_dashboard(): void
    {
        $user = User::factory()->create(['settings' => ['mealPreferences' => 'rice', 'mealAversion' => 'fish',
            'hideOrderingMealDescription' => true, 'hideDashboardMealDescription' => true]]);
        $avoided = Meal::factory()->create(['description' => 'fish']);
        $preferred = Meal::factory()->create(['description' => 'rice']);
        $user->order($preferred);
        $this->login($user)->get('/meals')->assertInertia(fn (Assert $page) => $page
            ->where('meals.0.id', $preferred->id)->where('meals.0.preferred', true)->where('meals.0.description', null)
            ->where('meals.1.id', $avoided->id)->where('meals.1.avoided', true));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('todayOrders.0.description', null));
    }

    #[Test]
    #[DataProvider('interfaces')]
    public function interface_switch_preserves_new_data_credentials_calendar_and_server_boundaries(bool $blade): void
    {
        config(['user-interface.blade' => $blade]);
        $this->freezeTime();
        $user = User::factory()->admin()->enabled()->create(['settings' => ['mealPreferences' => 'rice']]);
        $identity = ExternalIdentity::factory()->for($user)->create();
        $challenge = CredentialChallenge::factory()->for($user)->create();
        $meal = Meal::factory()->inFuture()->create(['title' => 'New secured meal', 'price' => 875]);
        $item = $user->order($meal);
        Deposit::factory()->create(['user_id' => $user->id, 'value' => 2500, 'comment' => 'New secured deposit']);
        $other = User::factory()->create();
        $foreign = OrderItem::factory()->inFuture()->create(['user_id' => $other->id]);
        $before = [];
        foreach (['users', 'external_identities', 'credential_challenges', 'meals', 'order_items', 'deposits'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        $this->login($user);
        foreach (['/' => 'Dashboard', '/my/orders' => 'My/Orders', '/my/balance' => 'My/Balance',
            '/meals?date='.$meal->date->toDateString() => 'Meals/Index', '/meals/'.$meal->id => 'Meals/Show'] as $url => $component) {
            $response = $this->get($url)->assertOk()->assertDontSee($user->password, false)
                ->assertDontSee($user->api_token, false)->assertDontSee($user->remember_token, false)
                ->assertDontSee($identity->subject, false)->assertDontSee($challenge->token_hash, false);
            if ($blade) {
                $response->assertDontSee('data-page=', false)->assertDontSee('/build/assets/', false);
            } else {
                $response->assertInertia(fn (Assert $page) => $page->component($component)->where('navigation.admin', '/admin/overview')->missing('user')->missing('auth'));
            }
            if (!$blade || $url !== '/my/balance') {
                $response->assertSee('New secured meal');
            }
        }
        $this->get('/my/balance')->assertSee('New secured deposit');
        $this->get('/profile/security')->assertInertia(fn (Assert $page) => $page->component('Profile/Security')->where('localCredentialStatus', 'enabled'));
        $this->get('/meals/ical')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('New secured meal (1)');
        $this->withExceptionHandling();
        $this->postJson('/order_items', ['meal_id' => $meal->id, 'quantity' => 0])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->postJson('/order_items', ['meal_id' => $meal->id, 'user_id' => $other->id])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->putJson('/order_items/'.$foreign->id, ['quantity' => 9])->assertForbidden();
        $this->post('/admin/order_items', ['meal_id' => $meal->id])->assertGone();
        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticatedAs($user);
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson(), $table);
        }
        $this->travelTo($meal->date->copy()->startOfDay());
        $this->putJson('/order_items/'.$item->id, ['quantity' => 9])->assertForbidden();
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'quantity' => 1]);
    }

    #[Test]
    public function active_inertia_navigation_performs_a_full_reload_when_blade_rollback_is_enabled(): void
    {
        config(['user-interface.blade' => true]);
        $this->login()->get('/my/orders', ['X-Inertia' => 'true'])->assertStatus(409)
            ->assertHeader('X-Inertia-Location', url('/my/orders'));
    }

    #[Test]
    public function token_calendar_survives_interface_changes_without_exposing_the_token_in_props(): void
    {
        $user = User::factory()->enabled()->create();
        $meal = Meal::factory()->inFuture()->create(['title' => 'Calendar fixture']);
        $user->order($meal);
        foreach ([false, true] as $blade) {
            config(['user-interface.blade' => $blade]);
            Auth::forgetGuards();
            $this->get('/meals/ical?api_token='.$user->api_token)->assertOk()
                ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->assertSee('Calendar fixture (1)');
        }
    }

    #[Test]
    public function invalid_date_and_provider_are_rejected_without_a_server_error(): void
    {
        $this->login()->withExceptionHandling();
        $this->getJson('/meals?date=not-a-date')->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->getJson('/meals?provider[]=unexpected')->assertUnprocessable()->assertJsonValidationErrors('provider');
    }

    public static function interfaces(): array
    {
        return ['Vue' => [false], 'Blade rollback' => [true]];
    }
}
