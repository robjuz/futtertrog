<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\User;
use Cknow\Money\Money;
use Illuminate\Http\Response;
use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class DepositTest extends TestCase
{


    #[\PHPUnit\Framework\Attributes\Test]
    public function only_admin_can_create_a_deposit()
    {
        $this->withExceptionHandling();

        $this->login()
            ->post(route('deposits.store'), [])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_admin_can_delete_a_deposit()
    {
        $this->withExceptionHandling();

        $deposit =  Deposit::factory()->create();

        $this->login()
            ->delete(route('deposits.destroy', $deposit))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_a_new_deposit()
    {
        $user = User::factory()->create();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageDeposits::class)->callAction('book', data: ['user_id' => $user->id, 'value' => 10])->assertHasNoActionErrors();

        $this->assertDatabaseHas('deposits', ['user_id' => $user->id, 'value' => 10]);
        $this->assertEquals(Money::parse(10), $user->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_create_a_negative_deposit()
    {
        $deposit = Deposit::factory()->raw(['value' => -10]);
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageDeposits::class)->callAction('book', data: $deposit)->assertHasNoActionErrors();

        $this->assertDatabaseHas('deposits', $deposit);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_delete_a_deposit()
    {
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create(['user_id' => $user->id, 'value' => 10]);
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageDeposits::class)->callTableAction('remove', $deposit)->assertNotified('Vorgang erfolgreich.');

        $this->assertModelMissing($deposit);
        $this->assertEquals(Money::parse(0), $user->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_to_make_a_transfer_from_one_user_to_other_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageDeposits::class)->callAction('transfer', data: ['source' => $user->id, 'target' => $otherUser->id, 'value' => 1050, 'comment' => 'transfer'])->assertHasNoActionErrors();

        $this->assertEquals(Money::parse(-1050), $user->fresh()->balance);
        $this->assertEquals(Money::parse(1050), $otherUser->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_the_description_in_edit_view()
    {
        $deposit = Deposit::factory()->create(['comment' => 'test description']);
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));

        Livewire::test(ManageDeposits::class)->mountTableAction('change', $deposit)->assertTableActionDataSet(['comment' => 'test description']);
    }
}
