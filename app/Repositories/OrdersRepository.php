<?php

namespace App\Repositories;

use App\User;
use Illuminate\Database\Eloquent\Builder;

class OrdersRepository
{
    public function usersTodayOrders(User $user, $date)
    {
        return $user->orderItems()->whereHas('order', function (Builder $query) use ($date) {
            $query->whereDate('date', $date);
        })->get();
    }

    public function usersMonthlyOrders(User $user, $date)
    {
        return $user->orderItems()
            ->with(['order', 'meal'])
            ->whereHas('order', function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            })
            ->get();
    }

    public function usersAllOrders(User $user, $fromDate = null, $toDate = null)
    {
        return $user->orderItems()
            ->with(['order', 'meal'])
            ->whereHas('order', function (Builder $query) use ($fromDate, $toDate) {
                $query->when($fromDate, function (Builder $query) use ($fromDate) {
                    return $query->whereDate('date', '>=', $fromDate);
                });
                $query->when($toDate, function (Builder $query) use ($toDate) {
                    return $query->whereDate('date', '<=', $toDate);
                });
            })
            ->get();
    }
}
