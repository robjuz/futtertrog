<?php

namespace Tests\Feature;

use App\Deposit;
use App\OrderItem;
use App\User;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * @runInSeparateProcess
 */
class UserTest extends TestCase
{

    /** @test */
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

    /** @test */
    public function users_are_not_allowed_to_manage_system_users()
    {
        $this->withExceptionHandling();

        $user = factory(User::class)->create();

        $this->login();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $user))->assertForbidden();
        $this->get(route('users.show', $user))->assertForbidden();
        $this->postJson(route('users.store'), [])->assertForbidden();
        $this->put(route('users.update', $user))->assertForbidden();
        $this->delete(route('users.destroy', $user))->assertForbidden();
    }

    /** @test */
    public function admin_can_see_a_list_of_system_users()
    {
        $users = factory(User::class, 5)->create();

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
        $data = factory(User::class)->raw([
            'name' => 'Example1',
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $data2 = factory(User::class)->raw([
            'name' => 'Example2',
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $this->loginAsAdmin();

        $this->get(route('users.create'))->assertViewIs('user.create');

        $this->post(route('users.store'), $data)->assertRedirect();
        $this->postJson(route('users.store'), $data2)->assertJsonFragment([ 'name' => 'Example2',]);

        $this->assertDatabaseHas('users', [
            'name' => $data['name'],
            'is_admin' => true
        ]);

        $user = factory(User::class)->create(['is_admin' => true]);

        $this->get(route('users.edit', $user))->assertSee($user->name);

        $this->get(route('users.show', $user))->assertSee($user->name);
        $this->getJson(route('users.show', $user))->assertJsonFragment([ 'name' => $user->name,]);

        $this->put(route('users.update', $user), ['name' => 'John'])->assertRedirect();
        $this->putJson(route('users.update', $user), ['name' => 'John'])->assertJsonFragment(['name' => 'John']);

        $this->assertDatabaseHas('users', [
            'name' => 'John',
            'is_admin' => true
        ]);

        $this->delete(route('users.destroy', $user))->assertRedirect();
        $this->assertDatabaseMissing('users', ['name' => 'John']);

        $user = factory(User::class)->create(['is_admin' => true]);
        $this->deleteJson(route('users.destroy', $user))->assertStatus(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function admin_can_see_users_order_history()
    {
        $user = factory(User::class)->create();

        $orderItem = factory(OrderItem::class)->make();
        $user->orderItems()->save($orderItem);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee($orderItem->meal->title);
    }

    /**
     * @test
     */
    public function admin_can_see_users_deposit_history()
    {
        $user = factory(User::class)->create();

        $deposit = factory(Deposit::class)->make(['value' => 999]);
        $user->deposits()->save($deposit);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee($deposit->value);
    }

    /** @test */
    public function admin_can_see_users_current_balance()
    {
        $user = factory(User::class)->create();

        $deposits = factory(Deposit::class, 2)->make(['value' => 10]);
        $user->deposits()->saveMany($deposits);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee(20);
    }
}
