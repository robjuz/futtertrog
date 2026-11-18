<?php

namespace App\Filament\Widgets;

use App\Models\AdminActivity;
use App\Models\Deposit;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdministrationStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Offene Sammelbestellungen', Order::where('status', Order::STATUS_OPEN)->count()),
            Stat::make('Einzahlungen in Bearbeitung', Deposit::where('status', Deposit::STATUS_PROCESSING)->count()),
            Stat::make('Anbieterergebnis zu klären', AdminActivity::where('action', 'order.auto_ordered')
                ->whereIn('state', ['running', 'uncertain'])->count()),
        ];
    }
}
