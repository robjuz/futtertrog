<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Response;
use Tests\TestCase;

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

    /** @test */
    public function admin_can_see_a_list_of_system_users()
    {
        $user = User::factory()->create();

        $this->loginAsAdmin($user);

        $response = $this->get(route('users.index'));
        $jsonResponse = $this->getJson(route('users.index'));

        $response->assertSee($user->name);

        $jsonResponse->assertJsonFragment($user->toArray());
    }

    /** @test */
    public function admin_can_manage_users()
    {
        $data = User::factory()->raw([
            'name' => 'Example1',
            'is_admin' => true,
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $data2 = User::factory()->raw([
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

        $user = User::factory()->create(['is_admin' => true]);

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
        $this->assertNull(User::find($user->id));

        $user = User::factory()->create(['is_admin' => true]);
        $this->deleteJson(route('users.destroy', $user))->assertStatus(Response::HTTP_NO_CONTENT);
    }

    /** @test */
    public function admin_can_see_users_order_history()
    {
        $user = User::factory()->create();

        $orderItem = OrderItem::factory()->make();
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
        $user = User::factory()->create();

        $deposit =  Deposit::factory()->make(['value' => 9900]);
        $user->deposits()->save($deposit);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee(99);
    }

    /** @test */
    public function admin_can_see_users_current_balance()
    {
        $user = User::factory()->create();

        $deposits = Deposit::factory()->count(2)->make(['value' => 10]);
        $user->deposits()->saveMany($deposits);

        $this->loginAsAdmin()
            ->get(route('users.show', $user))
            ->assertSee(20);
    }

    /** @test */
    public function user_cannot_delete_another_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();
        $this->login()->delete(route('users.destroy', $user))->assertForbidden();

        $user = User::factory()->create();
        $this->login()->deleteJson(route('users.destroy', $user))->assertForbidden();
    }

    /** @test */
    public function admin_can_delete_a_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();

        $this->loginAsAdmin()->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertFalse(User::all()->contains($user));

        $user = User::factory()->create();
        $this->loginAsAdmin()->deleteJson(route('users.destroy', $user))->assertSuccessful();
        $this->assertFalse(User::all()->contains($user));

    }

    /** @test */
    public function admin_can_restore_a_user()
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();
        $user->delete();

        // no admin user is not allowed to restore users
        $this->login()->post(route('users.restore', $user))->assertForbidden();

        $this->loginAsAdmin()->postJson(route('users.restore', $user))->assertSuccessful();

        $this->assertTrue(User::all()->contains($user));
    }
}
