<?php

namespace Tests\Feature;

use App\Deposit;
use App\User;
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

        $deposit = factory(Deposit::class)->create();

        $this->login()
            ->delete(route('deposits.destroy', $deposit))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** #test */
    public function admin_can_create_a_new_deposit()
    {
        $user = factory(User::class)->create();

        $deposit = factory(Deposit::class)->raw([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->loginAsAdmin()
            ->post(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
        $this->assertEqual(10, $user->balance);
    }

    /** @test */
    public function admin_can_create_a_negative_deposit()
    {
        $deposit = factory(Deposit::class)->raw(['value' => -10]);

        $this->loginAsAdmin()
            ->post(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
    }

    /** @test */
    public function admin_can_delete_a_deposit()
    {
        $user = factory(User::class)->create();

        $deposit = factory(Deposit::class)->create([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->loginAsAdmin()
            ->delete(route('deposits.destroy', $deposit));

        $this->assertDatabaseMissing('deposits', $deposit->toArray());

        $this->assertEquals(0, $user->balance);
    }

    /** @test */
    public function it_allows_to_make_a_transfer_from_one_user_to_other_user()
    {
        $user = factory(User::class)->create();

        $otherUser = factory(User::class)->create();

        $this->loginAsAdmin()
        ->postJson(route('deposits.transfer'), [
            'source' => $user->id,
            'target' => $otherUser->id,
            'value' => 10.5,
            'comment' => 'transfer'
        ]);

        $this->assertEquals(-10.5, $user->balance);
        $this->assertEquals(10.5, $otherUser->balance);
    }
}
