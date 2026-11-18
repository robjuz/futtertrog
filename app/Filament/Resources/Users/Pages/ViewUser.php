<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Deposit;
use Filament\Resources\Pages\ViewRecord;
use Livewire\WithPagination;

class ViewUser extends ViewRecord
{
    use WithPagination;

    protected static string $resource = UserResource::class;
    protected string $view = 'filament.resources.users.view-user';

    protected function authorizeAccess(): void
    {
        UserResource::getViewAnyAuthorizationResponse()->authorize();
        parent::authorizeAccess();
    }

    protected function getViewData(): array
    {
        $user = $this->getRecord();
        $user->loadMissing(['orderItems.meal.parent', 'payedOrders.orderItems.meal.parent']);

        return [
            'user' => $user,
            'balance' => $user->balance,
            'orders' => $user->orderItems()->with(['meal.parent', 'order'])->latest('id')->paginate(5, ['*'], 'ordersPage'),
            'deposits' => $user->deposits()->whereStatus(Deposit::STATUS_OK)->orderByDesc('id')->paginate(5, ['*'], 'depositsPage'),
        ];
    }
}
