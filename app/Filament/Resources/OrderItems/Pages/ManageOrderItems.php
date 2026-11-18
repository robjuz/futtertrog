<?php

namespace App\Filament\Resources\OrderItems\Pages;

use App\Filament\Resources\OrderItems\OrderItemResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOrderItems extends ManageRecords
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return OrderItemResource::headerActions();
    }
}
