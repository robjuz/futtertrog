<?php

namespace Tests\Feature;

use App\Actions\Admin\{Bookings, OrderItems, Orders, Users, SendNotification};
use App\Filament\Pages\Notifications;
use App\Filament\Resources\Deposits\Pages\ManageDeposits;
use App\Filament\Resources\OrderItems\Pages\ManageOrderItems;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\{Deposit, OrderItem, User};
use Filament\Facades\Filament;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use RuntimeException;
use Tests\TestCase;

class AdminResourceFailureTest extends TestCase
{
    public static function actions(): iterable
    {
        yield 'booking' => [ManageDeposits::class, 'book', Bookings::class, 'create', 'header'];
        yield 'transfer' => [ManageDeposits::class, 'transfer', Bookings::class, 'transfer', 'header'];
        yield 'change deposit' => [ManageDeposits::class, 'change', Bookings::class, 'update', 'deposit'];
        yield 'delete deposit' => [ManageDeposits::class, 'remove', Bookings::class, 'delete', 'deposit'];
        yield 'proxy order' => [ManageOrderItems::class, 'orderFor', OrderItems::class, 'create', 'header'];
        yield 'change item' => [ManageOrderItems::class, 'change', OrderItems::class, 'update', 'item'];
        yield 'delete item' => [ManageOrderItems::class, 'remove', OrderItems::class, 'delete', 'item'];
        yield 'change order' => [ManageOrders::class, 'change', Orders::class, 'update', 'order'];
        yield 'delete order' => [ManageOrders::class, 'remove', Orders::class, 'delete', 'order'];
        yield 'change role' => [ManageUsers::class, 'role', Users::class, 'changeRole', 'user'];
        yield 'deactivate' => [ManageUsers::class, 'deactivate', Users::class, 'deactivate', 'user'];
        yield 'restore' => [ManageUsers::class, 'restore', Users::class, 'restore', 'disabled'];
        yield 'notification' => [Notifications::class, 'send', SendNotification::class, 'execute', 'header'];
    }

    #[Test]
    #[DataProvider('actions')]
    public function each_failed_action_displays_an_error_without_claiming_success(string $page, string $action, string $service, string $method, string $target): void
    {
        $this->loginAsAdmin();
        Filament::setCurrentPanel(Filament::getPanel('administration'));
        $user = User::factory()->create(['deleted_at' => $target === 'disabled' ? now() : null]);
        $item = OrderItem::factory()->create(['user_id' => $user->id]);
        $deposit = Deposit::factory()->create(['user_id' => $user->id]);
        $this->mock($service)->shouldReceive($method)->once()->andThrow(new RuntimeException('private-service-detail'));
        $data = match ($action) {
            'book' => ['user_id' => $user->id, 'value' => 1234],
            'transfer' => ['source' => auth()->id(), 'target' => $user->id, 'value' => 1234],
            'orderFor' => ['user_id' => $user->id, 'meal_id' => $item->meal_id, 'quantity' => 1],
            'send' => ['user_id' => [$user->id], 'subject' => 'Test', 'body' => 'Test'],
            default => [],
        };
        $record = match ($target) {
            'deposit' => $deposit, 'item' => $item, 'order' => $item->order, 'user', 'disabled' => $user, default => null,
        };

        $component = Livewire::test($page);
        if ($record) {
            $component->callTableAction($action, $record, data: $data);
        } else {
            $component->callAction($action, data: $data);
        }

        $component->assertNotified('Vorgang nicht ausgeführt.')->assertDontSee('private-service-detail');
        $this->assertDatabaseCount('admin_activities', 0);
        $this->assertModelExists($deposit);
        $this->assertModelExists($item);
        $this->assertSame($target === 'disabled', $user->fresh()->trashed());
    }
}
