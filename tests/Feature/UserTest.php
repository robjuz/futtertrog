<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserTest extends TestCase
{

    /** @test */
    public function guests_are_not_allowed_to_manage_system_users()
    {
        $this->withExceptionHandling();

        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->get(route('users.edit', 1))->assertRedirect(route('login'));
        $this->get(route('users.show', 1))->assertRedirect(route('login'));
        $this->post(route('users.store'))->assertRedirect(route('login'));
        $this->put(route('users.update', 1))->assertRedirect(route('login'));
        $this->delete(route('users.destroy', 1))->assertRedirect(route('login'));
    }

    /** @test */
    public function users_are_not_allowed_to_manage_system_users()
    {
        $this->withExceptionHandling();

        $this->login();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', 1))->assertForbidden();
        $this->get(route('users.show', 1))->assertForbidden();
        $this->postJson(route('users.store'), [])->assertForbidden();
        $this->put(route('users.update', 1))->assertForbidden();
        $this->delete(route('users.destroy', 1))->assertForbidden();
    }

    /** @test */
    public function admin_can_see_a_list_of_system_users()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $users = factory('App\User', 10)->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        foreach ($users as $user) {
            $response->assertSee($user->name);
        }
    }

    /** @test */
    public function admin_can_manage_users()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $data = factory('App\User')->raw([
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $this->login($admin);

        $this->post(route('users.store'), $data);

        $this->assertDatabaseHas('users', [
           'name' => $data['name'],
           'is_admin' => true
        ]);

        $this->get(route('users.edit', 2))->assertSee($data['name']);

        $this->get(route('users.show', 2))->assertSee($data['name']);

        $this->put(route('users.update', 2), ['name' => 'John']);

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'is_admin' => '1'
        ]);

        $this->delete(route('users.destroy', 2));
        $this->assertDatabaseMissing('users',['name' => 'John']);
    }

    /** @test */
    public function admin_can_see_users_order_history()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $orderItem = factory('App\OrderItem')->make();
        $admin->orderItems()->save($orderItem);

        $this->login($admin)
            ->get(route('users.show', $admin))
            ->assertSee($orderItem->meal->title);
    }

    /** @test */
    public function admin_can_see_users_deposit_history()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $deposit = factory('App\Deposit')->make(['value' => 999]);
        $admin->deposits()->save($deposit);

        $this->login($admin)
            ->get(route('users.show', $admin))
            ->assertSee($deposit->value);
    }

    /** @test */
    public function admin_can_see_users_current_balance()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $deposits = factory('App\Deposit', 2)->make(['value' => 10]);
        $admin->deposits()->saveMany($deposits);

        $this->login($admin)
            ->get(route('users.show', $admin))
            ->assertSee(20);
    }
}
