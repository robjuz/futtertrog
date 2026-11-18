<?php

namespace Tests\Feature;

use App\Actions\Admin\{Meals, Users};
use App\Events\NewOrderPossibility;
use App\Filament\Resources\Meals\Pages\ManageMeals;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\{AdminActivity, Deposit, Meal, Order, OrderItem, User};
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Mail\CredentialConfirmation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Gate, Mail};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use App\UserSettings;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\{Event, Hash, Notification};
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{Test, TestWith};
use Tests\TestCase;

class AdminCompletionTest extends TestCase
{
    #[Test]
    #[TestWith([false, 'action'])]
    #[TestWith([true, 'action'])]
    #[TestWith([false, 'panel'])]
    #[TestWith([true, 'panel'])]
    public function manual_meal_creation_preserves_fields_event_date_and_opted_in_recipients(bool $notify, string $path): void
    {
        $this->freezeTime();
        $this->loginAsAdmin();
        $settings = new UserSettings();
        $settings->newOrderPossibilityNotification = true;
        $recipient = User::factory()->create(['settings' => $settings]);
        $other = User::factory()->create();
        $disabled = User::factory()->create(['settings' => $settings, 'deleted_at' => now()]);
        Notification::fake();
        $data = ['title' => 'Manuelles Gericht', 'description' => 'Beschreibung', 'price' => 1234, 'date' => '2026-11-20'];

        if ($path === 'action') {
            app(Meals::class)->create(auth()->user(), $data, $notify);
        } else {
            Filament::setCurrentPanel(Filament::getPanel('administration'));
            Livewire::test(ManageMeals::class)->callAction('add', data: [...$data, 'notify' => $notify])
                ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        }

        $this->assertDatabaseHas('meals', [...$data, 'date' => '2026-11-20 00:00:00']);
        $this->assertSame('1234', Meal::sole()->price->getAmount());
        if ($notify) {
            Notification::assertSentTo($recipient, \App\Notifications\NewOrderPossibility::class, fn ($notice): bool => $notice->toArray($recipient)['date']->toDateString() === '2026-11-20');
            Notification::assertCount(1);
        } else {
            Notification::assertNothingSent();
        }
        Notification::assertNotSentTo([$other, $disabled], \App\Notifications\NewOrderPossibility::class);
    }

    #[Test]
    public function administrators_create_disabled_credentials_and_edit_identity_without_changing_roles_or_secrets(): void
    {
        $actor = User::factory()->admin()->create();
        $user = app(Users::class)->create($actor, ['name' => 'Neue Person', 'email' => 'person@example.test', 'is_admin' => true]);

        $this->assertSame('disabled', $user->local_credential_status);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::isHashed($user->password));
        $password = $user->password;
        $token = $user->api_token;
        app(Users::class)->update($actor, $user, ['name' => 'Anderer Name', 'email' => 'other@example.test']);

        $user->refresh();
        $this->assertSame('Anderer Name', $user->name);
        $this->assertSame('other@example.test', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertSame($password, $user->password);
        $this->assertSame($token, $user->api_token);
        $this->assertSame('disabled', $user->local_credential_status);
        $this->assertSame(['is_admin' => true], AdminActivity::first()->changes['after']);
        $this->assertSame(['name' => 'Neue Person', 'email' => 'person@example.test'], AdminActivity::latest('id')->first()->changes['before']);
        $this->assertSame(['name' => 'Anderer Name', 'email' => 'other@example.test'], AdminActivity::latest('id')->first()->changes['after']);
        $this->assertStringNotContainsString($password, AdminActivity::all()->toJson());
        $this->assertStringNotContainsString($token, AdminActivity::all()->toJson());
    }

    public static function invalidIdentityData(): iterable
    {
        foreach (['create', 'update'] as $operation) {
            foreach ([['name' => ''], ['name' => str_repeat('x', 256)], ['email' => ''], ['email' => 'invalid'], ['email' => str_repeat('x', 256).'@example.test'],
                ['email' => 'taken@example.test'], ['password' => 'chosen-password'], ['password_confirmation' => 'chosen-password'], ['password' => null]] as $data) {
                yield $operation.' '.array_key_first($data).' '.json_encode($data) => [$operation, $data, array_key_first($data)];
            }
        }
        yield 'create invalid initial role' => ['create', ['is_admin' => 'invalid'], 'is_admin'];
        yield 'update role belongs to changeRole' => ['update', ['is_admin' => true], 'is_admin'];
    }

    #[Test]
    #[DataProvider('invalidIdentityData')]
    public function invalid_identity_changes_leave_users_and_audit_unchanged(string $operation, array $invalid, string $field): void
    {
        $actor = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'original@example.test']);
        User::factory()->create(['email' => 'taken@example.test', 'deleted_at' => now()]);
        $before = $user->fresh()->getAttributes();
        $data = [...['name' => 'New Name', 'email' => 'new@example.test'], ...$invalid];
        try {
            $operation === 'create' ? app(Users::class)->create($actor, $data) : app(Users::class)->update($actor, $user, $data);
            $this->fail('Invalid data must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
            $this->assertNotEmpty($exception->errors()[$field][0]);
        }
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    public function identity_whitelists_ignore_credential_tokens_and_other_unrecognized_fields(): void
    {
        $actor = User::factory()->admin()->create();
        $user = app(Users::class)->create($actor, ['name' => 'Name', 'email' => 'safe@example.test',
            'local_credential_status' => 'enabled', 'api_token' => 'injected', 'remember_token' => 'injected', 'deleted_at' => now()]);
        $this->assertSame('disabled', $user->local_credential_status);
        $this->assertNotSame('injected', $user->api_token);
        $this->assertNull($user->remember_token);
        $this->assertNull($user->deleted_at);
        app(Users::class)->update($actor, $user, ['name' => 'Updated', 'local_credential_status' => 'enabled', 'api_token' => 'injected']);
        $this->assertSame('disabled', $user->fresh()->local_credential_status);
        $this->assertNotSame('injected', $user->fresh()->api_token);
        $this->assertStringNotContainsString('injected', AdminActivity::all()->toJson());
    }

    public static function operationsAndActors(): iterable
    {
        foreach (['create-user', 'update-user', 'create-meal'] as $operation) {
            foreach (['user', 'revoked', 'disabled'] as $identity) {
                yield "$operation $identity" => [$operation, $identity];
            }
        }
    }

    private function executeOperation(string $operation, User $actor, User $target): void
    {
        match ($operation) {
            'create-user' => app(Users::class)->create($actor, ['name' => 'New', 'email' => 'new@example.test']),
            'update-user' => app(Users::class)->update($actor, $target, ['name' => 'Changed']),
            'create-meal' => app(Meals::class)->create($actor, ['title' => 'Meal', 'date' => '2026-11-20', 'price' => 1234], true),
        };
    }

    #[Test]
    #[DataProvider('operationsAndActors')]
    public function new_actions_authorize_the_current_actor_before_any_effect(string $operation, string $identity): void
    {
        $actor = User::factory()->create(['is_admin' => $identity !== 'user']);
        $target = User::factory()->create();
        $before = $target->fresh()->getAttributes();
        if ($identity === 'revoked') {
            User::whereKey($actor->id)->update(['is_admin' => false]);
        } elseif ($identity === 'disabled') {
            User::whereKey($actor->id)->delete();
        }
        Notification::fake();
        try {
            $this->executeOperation($operation, $actor, $target);
            $this->fail('Authorization must reject this actor.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $target->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('meals', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    #[TestWith(['create-user', 'create'])]
    #[TestWith(['update-user', 'update'])]
    #[TestWith(['create-meal', 'create'])]
    public function new_actions_apply_the_object_policy_in_addition_to_the_admin_boundary(string $operation, string $ability): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->create();
        Gate::before(fn (User $actor, string $checked): ?bool => $checked === $ability ? false : null);
        $this->expectException(AuthorizationException::class);
        $this->executeOperation($operation, $actor, $target);
    }

    #[Test]
    #[TestWith(['create-user'])]
    #[TestWith(['update-user'])]
    #[TestWith(['create-meal'])]
    public function failed_audits_roll_back_each_new_action_without_notifying(string $operation): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->create();
        $before = $target->fresh()->getAttributes();
        Notification::fake();
        AdminActivity::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            $this->executeOperation($operation, $actor, $target);
            $this->fail('The failing audit must abort the action.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        } finally {
            AdminActivity::flushEventListeners();
        }
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('meals', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function panel_creates_and_edits_users_without_a_password_field_and_offers_the_existing_confirmation(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Mail::fake();
        $page = Livewire::test(ManageUsers::class)->mountAction('create')
            ->assertSee('Passwortbestätigung senden')->assertDontSeeHtml('type="password"')
            ->fillForm(['name' => 'Panel Neu', 'email' => 'panel-new@example.test', 'is_admin' => true])
            ->callMountedAction()->assertHasNoActionErrors()->assertNotified('Benutzer angelegt.');
        $user = User::whereEmail('panel-new@example.test')->sole();
        $password = $user->password;
        $page->callTableAction('edit', $user, data: ['name' => 'Panel Anders', 'email' => 'panel-other@example.test'])
            ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        $user->refresh();
        $this->assertSame('Panel Anders', $user->name);
        $this->assertSame('panel-other@example.test', $user->email);
        $this->assertSame($password, $user->password);
        $this->assertTrue($user->is_admin);
        $this->assertSame('disabled', $user->local_credential_status);
        $this->assertDatabaseCount('admin_activities', 2);
        $state = json_encode($page->snapshot);
        foreach ([$password, $user->api_token, 'local_credential_status', 'remember_token'] as $secret) {
            $this->assertStringNotContainsString($secret, $state);
        }
        $page->callTableAction('passwordLink', $user)->assertNotified('Vorgang erfolgreich.');
        Mail::assertSent(CredentialConfirmation::class, fn ($mail): bool => $mail->hasTo('panel-other@example.test'));
        $this->assertSame('disabled', $user->fresh()->local_credential_status);
    }

    #[Test]
    #[TestWith(['create'])]
    #[TestWith(['edit'])]
    public function panel_identity_validation_and_duplicate_email_are_explained_without_changes(string $action): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $user = User::factory()->create();
        $taken = User::factory()->create(['deleted_at' => now()]);
        $page = Livewire::test(ManageUsers::class);
        if ($action === 'create') {
            $page->callAction($action, data: ['name' => '', 'email' => 'invalid'])->assertHasActionErrors(['name', 'email']);
            $page->unmountAction()->callAction($action, data: ['name' => 'New', 'email' => $taken->email])->assertHasActionErrors(['email']);
        } else {
            $page->callTableAction($action, $user, data: ['name' => '', 'email' => 'invalid'])->assertHasActionErrors(['name', 'email']);
            $page->unmountAction()->callTableAction($action, $user, data: ['name' => 'New', 'email' => $taken->email])->assertHasActionErrors(['email']);
        }
        $page->assertHasActionErrors(['email' => 'unique']);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('admin_activities', 0);
        $this->assertNotSame('New', $user->fresh()->name);
    }

    #[Test]
    #[TestWith(['create-user'])]
    #[TestWith(['update-user'])]
    #[TestWith(['create-meal'])]
    public function panel_failed_audits_explain_failure_without_persisting_or_notifying(string $operation): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $target = User::factory()->create();
        Notification::fake();
        AdminActivity::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            $page = match ($operation) {
                'create-user' => Livewire::test(ManageUsers::class)->callAction('create', data: ['name' => 'New', 'email' => 'new@example.test']),
                'update-user' => Livewire::test(ManageUsers::class)->callTableAction('edit', $target, data: ['name' => 'Changed', 'email' => 'new@example.test']),
                'create-meal' => Livewire::test(ManageMeals::class)->callAction('add', data: ['title' => 'Meal', 'date' => '2026-11-20', 'price' => 1234, 'notify' => true]),
            };
            $page->assertNotified('Vorgang nicht ausgeführt.');
        } finally {
            AdminActivity::flushEventListeners();
        }
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('meals', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        $this->assertNotSame('Changed', $target->fresh()->name);
        Notification::assertNothingSent();
    }

    #[Test]
    public function user_details_are_read_only_paginated_escaped_and_use_the_existing_balance_without_n_plus_one(): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $user = User::factory()->create(['name' => '<script>person</script>']);
        $parent = Meal::factory()->create(['title' => '<script>meal</script>']);
        $variant = Meal::factory()->create(['title' => 'Variante', 'parent_id' => $parent->id, 'price' => 100]);
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->count(6)->create(['user_id' => $user->id, 'meal_id' => $variant->id, 'order_id' => $order->id, 'quantity' => 2]);
        Deposit::factory()->count(6)->create(['user_id' => $user->id, 'value' => 1000, 'comment' => '<script>booking</script>']);
        Deposit::factory()->create(['user_id' => $user->id, 'value' => 99999, 'status' => 'processing', 'comment' => 'Pending hidden']);
        $other = User::factory()->create();
        Deposit::factory()->create(['user_id' => $other->id, 'comment' => 'Other account hidden']);
        Model::preventLazyLoading();
        try {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $page = Livewire::test(ViewUser::class, ['record' => $user->id])->assertOk()
                ->assertSee('Guthaben:')->assertSee((string) \Cknow\Money\Money::EUR(6000))->assertSee('Bestellverlauf')->assertSee('Buchungsverlauf')
                ->assertDontSeeHtml('<script>person</script>')->assertDontSeeHtml('<script>meal</script>')->assertDontSeeHtml('<script>booking</script>')
                ->assertDontSee('Pending hidden')->assertDontSee('Other account hidden')->assertDontSeeHtml('type="password"');
            $small = count(DB::getQueryLog());
            DB::disableQueryLog();
            OrderItem::factory()->count(12)->create(['user_id' => $user->id, 'meal_id' => $variant->id, 'order_id' => $order->id]);
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test(ViewUser::class, ['record' => $user->id])->assertOk();
            $large = count(DB::getQueryLog());
            $this->assertLessThanOrEqual($small + 1, $large);
            $page->call('gotoPage', 2, 'ordersPage')->assertSet('paginators.ordersPage', 2)->assertSet('paginators.depositsPage', 1);
            $page->call('gotoPage', 2, 'depositsPage')->assertSet('paginators.ordersPage', 2)->assertSet('paginators.depositsPage', 2);
            $state = json_encode($page->snapshot);
            $this->assertStringNotContainsString($user->password, $state);
            $this->assertStringNotContainsString($user->api_token, $state);
        } finally {
            Model::preventLazyLoading(false);
            DB::disableQueryLog();
        }
        $this->assertDatabaseCount('admin_activities', 0);
    }

    #[Test]
    #[TestWith([1234])]
    #[TestWith([12.34])]
    public function manual_meal_prices_keep_the_existing_integer_cent_and_float_euro_convention(int|float $price): void
    {
        $actor = User::factory()->admin()->create();
        $meal = app(Meals::class)->create($actor, ['title' => 'Meal', 'price' => $price, 'date' => '2026-11-20']);
        $this->assertSame('1234', $meal->price->getAmount());
        $activity = AdminActivity::sole();
        $this->assertSame($actor->id, $activity->actor_id);
        $this->assertSame(Meal::class, $activity->target_type);
        $this->assertSame((string) $meal->id, $activity->target_id);
        $this->assertSame('meal.created', $activity->action);
        $this->assertSame(1234, $activity->changes['after']['price']);
    }

    public static function invalidMealData(): iterable
    {
        yield 'missing required fields' => [[], ['title', 'date', 'price']];
        foreach ([['title' => str_repeat('x', 256)], ['description' => ['invalid']], ['date' => 'invalid'],
            ['price' => -1], ['price' => 'invalid'], ['provider' => 'Unknown'], ['parent_id' => 99999],
            ['can_be_ordered_until' => '2026-11-19']] as $invalid) {
            yield array_key_first($invalid).' '.json_encode($invalid) => [[...['title' => 'Meal', 'date' => '2026-11-20', 'price' => 1234], ...$invalid], array_keys($invalid)];
        }
    }

    #[Test]
    #[DataProvider('invalidMealData')]
    public function invalid_manual_meals_neither_persist_log_nor_notify(array $data, array $fields): void
    {
        $actor = User::factory()->admin()->create();
        Notification::fake();
        try {
            app(Meals::class)->create($actor, $data, true);
            $this->fail('Invalid meals must fail validation.');
        } catch (ValidationException $exception) {
            $this->assertSame($fields, array_keys($exception->errors()));
        }
        $this->assertDatabaseCount('meals', 0);
        $this->assertDatabaseCount('admin_activities', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function manual_meal_creation_dispatches_the_existing_event_once_with_the_saved_date(): void
    {
        $actor = User::factory()->admin()->create();
        Event::fake([NewOrderPossibility::class]);
        app(Meals::class)->create($actor, ['title' => 'Silent', 'date' => '2026-11-20', 'price' => 1234]);
        Event::assertNotDispatched(NewOrderPossibility::class);
        app(Meals::class)->create($actor, ['title' => 'Notify', 'date' => '2026-11-21', 'price' => 1234], true);
        Event::assertDispatched(NewOrderPossibility::class, fn ($event): bool => $event->date->toDateString() === '2026-11-21');
        Event::assertDispatchedTimes(NewOrderPossibility::class, 1);
    }

    #[Test]
    public function administrators_have_no_http_or_panel_path_to_choose_another_accounts_password(): void
    {
        $this->loginAsAdmin()->withExceptionHandling();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $user = User::factory()->create();
        $password = $user->password;
        $data = ['name' => 'Injected', 'email' => 'injected@example.test', 'password' => 'admin-chosen-password', 'password_confirmation' => 'admin-chosen-password'];
        foreach ([['POST', route('users.store')], ['PUT', route('users.update', $user)], ['PATCH', route('users.update', $user)],
            ['DELETE', route('users.destroy', $user)], ['POST', route('users.restore', $user)]] as [$method, $url]) {
            $this->json($method, $url, $data)->assertStatus(410);
        }
        foreach ([['POST', '/users'], ['PUT', '/users/'.$user->id], ['PATCH', '/users/'.$user->id]] as [$method, $url]) {
            $this->assertContains($this->json($method, $url, $data)->status(), [404, 405]);
        }
        Livewire::test(ManageUsers::class)->mountTableAction('edit', $user)
            ->assertDontSeeHtml('type="password"')
            ->set('mountedActions.0.data.password', 'admin-chosen-password')
            ->set('mountedActions.0.data.password_confirmation', 'admin-chosen-password')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame($password, $user->fresh()->password);
        Livewire::test(ManageUsers::class)->mountAction('create')
            ->fillForm(['name' => 'New account', 'email' => 'new-safe@example.test'])
            ->set('mountedActions.0.data.password', 'admin-chosen-password')
            ->callMountedAction()->assertHasNoActionErrors();
        $created = User::whereEmail('new-safe@example.test')->sole();
        $this->assertFalse(Hash::check('admin-chosen-password', $created->password));
        $this->assertSame('disabled', $created->local_credential_status);
        $this->assertStringNotContainsString('admin-chosen-password', AdminActivity::all()->toJson());
    }

    #[Test]
    #[TestWith(['guest'])]
    #[TestWith(['user'])]
    #[TestWith(['revoked'])]
    #[TestWith(['disabled'])]
    public function an_open_user_history_rechecks_authorization_before_paginating(string $identity): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->create();
        $this->login($actor)->withExceptionHandling();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $page = Livewire::test(ViewUser::class, ['record' => $target->id]);
        if ($identity === 'guest') {
            auth()->guard('web')->logout();
        } elseif ($identity === 'user') {
            $this->login(User::factory()->create());
        } elseif ($identity === 'revoked') {
            User::whereKey($actor->id)->update(['is_admin' => false]);
        } else {
            User::whereKey($actor->id)->delete();
        }
        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), [
            'components' => [['snapshot' => json_encode($page->snapshot), 'updates' => [], 'calls' => [['method' => 'gotoPage', 'params' => [2, 'ordersPage']]]]],
        ])->assertForbidden()->assertDontSee($target->email);
    }
}
