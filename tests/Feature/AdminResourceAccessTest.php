<?php

namespace Tests\Feature;

use App\Filament\Pages\{Dashboard, Notifications};
use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use App\Filament\Resources\Meals\Pages\ManageMeals;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\{Deposit, Meal, OrderItem, User};
use Illuminate\Support\Facades\{Http, Mail, Notification};
use Filament\Facades\Filament;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use Tests\TestCase;

class AdminResourceAccessTest extends TestCase
{
    public static function pagesAndIdentities(): iterable
    {
        foreach ([ManageDeposits::class, ManageMeals::class, ManageOrders::class, ManageOrderItems::class, ManageUsers::class, Notifications::class, Dashboard::class] as $page) {
            foreach (['guest', 'user', 'disabled-admin'] as $identity) {
                yield "$page $identity" => [$page, $identity];
            }
        }
    }

    #[Test]
    #[DataProvider('pagesAndIdentities')]
    public function each_page_has_its_own_server_authorization(string $page, string $identity): void
    {
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        if ($identity !== 'guest') {
            $user = User::factory()->create(['is_admin' => $identity === 'disabled-admin']);
            $this->login($user);
            if ($identity === 'disabled-admin') {
                User::whereKey($user->id)->delete();
            }
        }
        $this->withExceptionHandling();

        Livewire::test($page)->assertForbidden();

        $this->assertDatabaseCount('admin_activities', 0);
    }

    public static function actionPages(): iterable
    {
        yield 'booking' => [ManageDeposits::class, 'book'];
        yield 'transfer' => [ManageDeposits::class, 'transfer'];
        yield 'meal' => [ManageMeals::class, 'add'];
        yield 'import' => [ManageMeals::class, 'import'];
        yield 'proxy order' => [ManageOrderItems::class, 'orderFor'];
        yield 'notification' => [Notifications::class, 'send'];
    }

    #[Test]
    #[DataProvider('actionPages')]
    public function an_open_action_cannot_run_after_admin_rights_are_revoked(string $page, string $action): void
    {
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $actor = User::factory()->admin()->create();
        $this->login($actor);
        $this->withExceptionHandling();
        $component = Livewire::test($page)->mountAction($action);
        User::whereKey($actor->id)->update(['is_admin' => false]);

        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), [
            'components' => [['snapshot' => json_encode($component->snapshot), 'updates' => [], 'calls' => [['method' => 'callMountedAction', 'params' => []]]]],
        ])->assertForbidden();

        $this->assertDatabaseCount('admin_activities', 0);
    }

    public static function actionsAndIdentities(): iterable
    {
        $actions = [
            [ManageDeposits::class, 'book', 'header'], [ManageDeposits::class, 'transfer', 'header'],
            [ManageDeposits::class, 'change', 'deposit'], [ManageDeposits::class, 'remove', 'deposit'],
            [ManageMeals::class, 'add', 'header'], [ManageMeals::class, 'import', 'header'],
            [ManageMeals::class, 'change', 'meal'], [ManageMeals::class, 'remove', 'meal'],
            [ManageOrders::class, 'change', 'order'], [ManageOrders::class, 'remove', 'order'], [ManageOrders::class, 'autoOrder', 'order'],
            [ManageOrderItems::class, 'orderFor', 'header'], [ManageOrderItems::class, 'change', 'item'], [ManageOrderItems::class, 'remove', 'item'],
            [ManageUsers::class, 'role', 'user'], [ManageUsers::class, 'deactivate', 'user'],
            [ManageUsers::class, 'restore', 'disabled'], [ManageUsers::class, 'passwordLink', 'user'],
            [Notifications::class, 'send', 'header'],
        ];
        foreach ($actions as [$page, $action, $target]) {
            foreach (['guest', 'user', 'disabled-admin'] as $identity) {
                yield "$page $action $identity" => [$page, $action, $target, $identity];
            }
        }
    }

    #[Test]
    #[DataProvider('actionsAndIdentities')]
    public function every_mounted_write_action_rechecks_the_session_boundary(string $page, string $action, string $target, string $identity): void
    {
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $actor = User::factory()->admin()->create();
        $recipient = User::factory()->create(['deleted_at' => $target === 'disabled' ? now() : null]);
        $item = OrderItem::factory()->create(['user_id' => $recipient->id]);
        $meal = Meal::factory()->create();
        $deposit = Deposit::factory()->create(['user_id' => $recipient->id]);
        $record = match ($target) {
            'deposit' => $deposit, 'meal' => $meal, 'order' => $item->order, 'item' => $item,
            'user', 'disabled' => $recipient, default => null,
        };
        $this->login($actor)->withExceptionHandling();
        $component = Livewire::test($page);
        if ($target === 'disabled') {
            $component->filterTable('trashed', '0');
        }
        if ($record) {
            $component->mountTableAction($action, $record)
                ->assertActionMounted(\Filament\Actions\Testing\TestAction::make($action)->table($record));
        } else {
            $component->mountAction($action)->assertActionMounted($action);
        }
        Http::preventStrayRequests();
        Mail::fake();
        Notification::fake();
        if ($identity === 'guest') {
            auth()->guard('web')->logout();
        } elseif ($identity === 'user') {
            $this->login(User::factory()->create());
        } else {
            User::whereKey($actor->id)->delete();
        }

        $response = $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), [
            'components' => [['snapshot' => json_encode($component->snapshot), 'updates' => [], 'calls' => [['method' => 'callMountedAction', 'params' => []]]]],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('admin_activities', 0);
        $this->assertModelExists($deposit);
        $this->assertModelExists($item);
        $this->assertModelExists($meal);
        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }
}
