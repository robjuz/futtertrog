<?php

namespace Tests\Feature;

use Illuminate\Http\Response;
use Tests\TestCase;

class DepositTest extends TestCase
{


    /** @test */
    public function only_admin_can_create_a_deposit()
    {
        $this->withExceptionHandling();

        $this->login()
            ->post(route('deposits.store'), [])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function only_admin_can_delete_a_deposit()
    {
        $this->withExceptionHandling();

        $deposit = factory('App\Deposit')->create();

        $this->login()
            ->delete(route('deposits.destroy', $deposit))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** #test */
    public function admin_can_create_a_new_deposit()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $user = factory('App\User')->create();

        $deposit = factory('App\Deposit')->raw([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->login($admin);

        $this->post(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
        $this->assertEqual(10, $user->balance);
    }

    /** @test */
    public function admin_can_create_a_negative_deposit()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $deposit = factory('App\Deposit')->raw(['value' => -10]);

        $this->login($admin)
            ->post(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
    }

    /** @test */
    public function admin_can_delete_a_deposit()
    {
        $admin = factory('App\User')->create(['is_admin' => true]);

        $user = factory('App\User')->create();

        $deposit = factory('App\Deposit')->create([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->login($admin)
            ->delete(route('deposits.destroy', $deposit));

        $this->assertDatabaseMissing('deposits', $deposit->toArray());

        $this->assertEquals(0, $user->balance);
    }
}
