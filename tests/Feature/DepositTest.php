<?php

namespace Tests\Feature;

use App\Deposit;
use App\User;
use Cknow\Money\Money;
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

        $deposit =  Deposit::factory()->create();

        $this->login()
            ->delete(route('deposits.destroy', $deposit))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** #test */
    public function admin_can_create_a_new_deposit()
    {
        $user = User::factory()->create();

        $deposit =  Deposit::factory()->raw([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->loginAsAdmin()
            ->post(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
        $this->assertEquals(10, $user->balance);
    }

    /** @test */
    public function admin_can_create_a_negative_deposit()
    {
        $deposit =  Deposit::factory()->raw(['value' => -10]);

        $this->loginAsAdmin()
            ->postJson(route('deposits.store'), $deposit);

        $this->assertDatabaseHas('deposits', $deposit);
    }

    /** @test */
    public function admin_can_delete_a_deposit()
    {
        $user = User::factory()->create();

        $deposit =  Deposit::factory()->create([
            'user_id' => $user->id,
            'value' => 10,
        ]);

        $this->loginAsAdmin()
            ->delete(route('deposits.destroy', $deposit));

        $this->assertDatabaseMissing('deposits', $deposit->toArray());

        $this->assertEquals(Money::parse(0), $user->balance);
    }

    /** @test */
    public function it_allows_to_make_a_transfer_from_one_user_to_other_user()
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $this->loginAsAdmin()
        ->postJson(route('deposits.transfer'), [
            'source' => $user->id,
            'target' => $otherUser->id,
            'value' => 10.5,
            'comment' => 'transfer'
        ]);

        $this->assertEquals(Money::parse(-1050), $user->balance);
        $this->assertEquals(Money::parse(1050), $otherUser->balance);
    }

    /** @test  */
    public function it_shows_the_description_in_edit_view()
    {
        /** @var Deposit $deposit */
        $deposit =  Deposit::factory()->create(['comment' => 'test description']);

        $this->loginAsAdmin();

            $this->get(route('deposits.edit', $deposit))
            ->assertSeeText('test description');

    }
}
