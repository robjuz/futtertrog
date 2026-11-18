<?php

namespace App\Filament\Widgets;

use App\Models\AdminActivity;
use App\Models\Deposit;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdministrationStats extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return \App\Filament\Pages\Dashboard::canAccess();
    }

    protected function getStats(): array
    {
        return [
            Stat::make(__('System balance'), (string) app('system_balance')),
            Stat::make('Offene Sammelbestellungen', Order::where('status', Order::STATUS_OPEN)->count()),
            Stat::make('Einzahlungen in Bearbeitung', Deposit::where('status', Deposit::STATUS_PROCESSING)->count()),
            Stat::make('Anbieterergebnis zu klären', AdminActivity::where('action', 'order.auto_ordered')
                ->whereIn('state', ['running', 'uncertain'])->count()),
        ];
    }
}
