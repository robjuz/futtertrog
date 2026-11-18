<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;
use App\Filament\Resources\Users\{UserResource, Pages\ManageUsers, Pages\ViewUser};
use Filament\Facades\Filament;
use Livewire\Livewire;

class UserTest extends TestCase
{

    #[\PHPUnit\Framework\Attributes\Test]
    public function guests_are_not_allowed_to_manage_system_users()
    {
        $this->withExceptionHandling();

        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->getJson(route('users.index'))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->getJson(route('users.create'))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->get(route('users.edit', 1))->assertRedirect(route('login'));
        $this->getJson(route('users.edit', 1))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->get(route('users.show', 1))->assertRedirect(route('login'));
        $this->getJson(route('users.show', 1))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->post(route('users.store'))->assertRedirect(route('login'));
        $this->postJson(route('users.store'))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->put(route('users.update', 1))->assertRedirect(route('login'));
        $this->putJson(route('users.update', 1))->assertStatus(Response::HTTP_UNAUTHORIZED);

        $this->delete(route('users.destroy', 1))->assertRedirect(route('login'));
        $this->deleteJson(route('users.destroy', 1))->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function users_are_not_allowed_to_manage_system_users()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();

        $this->login();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $user))->assertForbidden();
        $this->get(route('users.show', $user))->assertForbidden();
        $this->postJson(route('users.store'), [])->assertForbidden();
        $this->put(route('users.update', $user))->assertForbidden();
        $this->delete(route('users.destroy', $user))->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_a_list_of_system_users()
    {
        $user = User::factory()->create();

        $this->loginAsAdmin();

        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $this->get(route('users.index'))->assertRedirect(UserResource::getUrl());
        Livewire::test(ManageUsers::class)->assertCanSeeTableRecords([$user])->assertSee($user->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_manage_users()
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $this->get(route('users.create'))->assertRedirect(UserResource::getUrl(parameters: ['action' => 'create']));
        $page = Livewire::test(ManageUsers::class);
        foreach (['Example1', 'Example2'] as $name) {
            $page->callAction('create', data: ['name' => $name, 'email' => strtolower($name).'@example.test', 'is_admin' => true])
                ->assertHasNoActionErrors()->assertNotified('Vorgang erfolgreich.');
        }
        $user = User::whereName('Example1')->sole();
        $this->assertTrue($user->is_admin);
        $this->assertSame('disabled', $user->local_credential_status);
        $this->get(route('users.edit', $user))->assertRedirect(UserResource::getUrl(parameters: ['tableAction' => 'edit', 'tableActionRecord' => $user->id]));
        $this->get(route('users.show', $user))->assertRedirect(UserResource::getUrl('view', ['record' => $user]));
        $page->callTableAction('edit', $user, data: ['name' => 'John', 'email' => $user->email])->assertHasNoActionErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'John', 'is_admin' => true]);
        $page->callTableAction('deactivate', $user)->assertNotified('Vorgang erfolgreich.');
        $this->assertSoftDeleted($user);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_users_order_history()
    {
        $user = User::factory()->create();

        $orderItem = OrderItem::factory()->make();
        $user->orderItems()->save($orderItem);

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ViewUser::class, ['record' => $user->id])->assertOk()->assertSee($orderItem->meal->title);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_users_deposit_history()
    {
        $user = User::factory()->create();

        $deposit =  Deposit::factory()->make(['value' => 9900]);
        $user->deposits()->save($deposit);

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ViewUser::class, ['record' => $user->id])->assertOk()->assertSee((string) \Cknow\Money\Money::EUR(9900));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_see_users_current_balance()
    {
        $user = User::factory()->create();

        $deposits = Deposit::factory()->count(2)->make(['value' => 10]);
        $user->deposits()->saveMany($deposits);

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ViewUser::class, ['record' => $user->id])->assertOk()->assertSee((string) \Cknow\Money\Money::EUR(20));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_delete_another_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();
        $this->login()->delete(route('users.destroy', $user))->assertForbidden();

        $user = User::factory()->create();
        $this->login()->deleteJson(route('users.destroy', $user))->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_delete_a_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ManageUsers::class)->callTableAction('deactivate', $user)->assertNotified('Vorgang erfolgreich.');
        $this->assertSoftDeleted($user);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_restore_a_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();
        $user->delete();

        // no admin user is not allowed to restore users
        $this->login()->post(route('users.restore', $user))->assertForbidden();

        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        Livewire::test(ManageUsers::class)->filterTable('trashed', '0')->callTableAction('restore', $user)->assertNotified('Vorgang erfolgreich.');

        $this->assertTrue(User::all()->contains($user));
    }
}
