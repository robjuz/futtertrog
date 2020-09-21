<?php

namespace App\Repositories;

use App\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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

    public function get(Request $request)
    {
        $from = Carbon::parse($request->query('from', today()));
        $to = $request->has('to') && ! empty($request->to) ? Carbon::parse($request->to) : null;

        return Order::with(['orderItems.meal'])
            ->whereHas('orderItems.meal')
            ->whereDate('date', '>=', $from->toDateString())
            ->when(
                ! empty($to),
                function (Builder $query) use ($to) {
                    $query->whereDate('date', '<=', $to->toDateString());
                }
            )
            ->when(
                $request->input('user_id', null),
                function (Builder $query) use ($request) {
                    $query->with(
                        [
                            'orderItems' => function ($query) use ($request) {
                                $query->whereUserId($request->user_id);
                            },
                            'orderItems.user',
                        ]
                    );
                    $query->whereHas(
                        'orderItems',
                        function (Builder $query) use ($request) {
                            $query->whereUserId($request->user_id);
                        }
                    );
                },
                function (Builder $query) {
                    $query->with(['orderItems.user']);
                }
            )
            ->orderBy('date')
            ->get();
    }
}
