<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
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
        $users = factory('App\User', 5)->create();

        $this->loginAsAdmin();

        $response = $this->get(route('users.index'));
        $jsonResponse = $this->getJson(route('users.index'));

        foreach ($users as $user) {
            $response->assertSee($user->name);
        }

        $jsonResponse->assertJson($users->toArray());
    }

    /** @test */
    public function admin_can_manage_users()
    {
        $data = factory('App\User')->raw([
            'name' => 'Example1',
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $data2 = factory('App\User')->raw([
            'name' => 'Example2',
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $this->loginAsAdmin();

        $this->post(route('users.store'), $data)->assertRedirect();
        $this->postJson(route('users.store'), $data2)->assertJsonFragment([ 'name' => 'Example2',]);

        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'is_admin' => true
        ]);

        $this->get(route('users.edit', 2))->assertSee($data['name']);

        $this->get(route('users.show', 2))->assertSee($data['name']);
        $this->getJson(route('users.show', 2))->assertJsonFragment([ 'name' => 'Example1',]);

        $this->put(route('users.update', 2), ['name' => 'John'])->assertRedirect();
        $this->putJson(route('users.update', 2), ['name' => 'John'])->assertJsonFragment(['name' => 'John']);

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'is_admin' => true
        ]);

        $this->delete(route('users.destroy', 2))->assertRedirect();
        $this->assertDatabaseMissing('users', ['name' => 'John']);

        $this->deleteJson(route('users.destroy', 3))->assertStatus(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function admin_can_see_users_order_history()
    {
        $user = factory('App\User')->create();

        $orderItem = factory('App\OrderItem')->make();
        $user->orderItems()->save($orderItem);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee($orderItem->meal->title);
    }

    /** @test */
    public function admin_can_see_users_deposit_history()
    {
        $user = factory('App\User')->create();

        $deposit = factory('App\Deposit')->make(['value' => 999]);
        $user->deposits()->save($deposit);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee($deposit->value);
    }

    /** @test */
    public function admin_can_see_users_current_balance()
    {
        $user = factory('App\User')->create();

        $deposits = factory('App\Deposit', 2)->make(['value' => 10]);
        $user->deposits()->saveMany($deposits);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee(20);
    }
}
