<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class Dashboard extends \Filament\Pages\Dashboard
{
    public static function canAccess(): bool
    {
        $actor = User::withTrashed()->find(auth()->id());

        return $actor && Gate::forUser($actor)->allows('list', Order::class);
    }
}
