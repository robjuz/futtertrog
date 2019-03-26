<?php

namespace App\Repositories;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class OrdersRepository
{
    public function userOrdersForDate($date, User $user = null)
    {
        $user = $user ?? auth()->user();

        $date = Carbon::parse($date);

        return $user->orderItems()->whereHas('order', function (Builder $query) use ($date) {
            $query->whereDate('date', $date);
        })->get();
    }

    public function usersAllOrders(User $user, $fromDate = null, $toDate = null)
    {
        $fromDate = $fromDate ? Carbon::parse($fromDate)->toDateString() : null;
        $toDate = $toDate ? Carbon::parse($toDate)->toDateString() : null;

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
