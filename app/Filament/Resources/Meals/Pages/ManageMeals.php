<?php

namespace App\Filament\Resources\Meals\Pages;

use App\Filament\Resources\Meals\MealResource;
use Filament\Resources\Pages\ManageRecords;

class ManageMeals extends ManageRecords
{
    protected static string $resource = MealResource::class;

    protected function getHeaderActions(): array
    {
        return MealResource::headerActions();
    }
}
