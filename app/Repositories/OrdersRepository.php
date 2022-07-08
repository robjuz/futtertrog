<?php

namespace App\Repositories;

use App\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrdersRepository
{
    public function get(Request $request)
    {
        $from = Carbon::parse($request->get('from', today()));
        $to = $request->has('to') && !empty($request->to) ? Carbon::parse($request->to) : null;

        return Order::with(['orderItems.meal', 'orderItems.user'])
            ->withMin('meals', 'date')
            ->withMax('meals', 'date')
            ->whereHas(
                'orderItems.meal',
                function(Builder $qurey) use ($from, $to) {
                    $qurey->whereDate('date', '>=', $from->toDateString())
                        ->when(
                            !empty($to),
                            function (Builder $query) use ($to) {
                                $query->whereDate('date', '<=', $to->toDateString());
                            }
                        );
                    }
            )
            ->when(
                $request->has('user_id', null),
                function (Builder $query) use ($request) {
                    $query->whereRelation('orderItems', 'user_id', $request->user_id);
                }
            )
            ->get();
    }

    public function usersAllOrders(User $user, $fromDate = null, $toDate = null)
    {
        $fromDate = $fromDate ? Carbon::parse($fromDate)->toDateString() : null;
        $toDate = $toDate ? Carbon::parse($toDate)->toDateString() : null;

        return $user->orderItems()
            ->with(['meal'])
            ->whereHas('meal', function (Builder $query) use ($fromDate, $toDate) {
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
