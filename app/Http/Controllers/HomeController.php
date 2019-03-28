<?php

namespace App\Http\Controllers;

use App\Deposit;
use App\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        /** @var User $user */
        $user = $request->user();

        $balance = $user->balance;
        $todayOrders = $user->orderItems()
            ->with('meal')
            ->whereHas(
                'order',
                function ($query) {
                    $query->whereDate('date', today());
                }
            )
            ->get();

        $ordersHistory = $user->orderItems()
            ->with(['order', 'meal'])
            ->latest()
            ->simplePaginate(5, ['*'], 'meals_page');
        $ordersHistory->fragment('order-history');
        $ordersHistory->appends('deposits_page', $request->deposits_page);
        $ordersHistory->appends('future_meals_page', $request->future_meals_page);

        $futureOrders = $user->orderItems()
            ->with(['order', 'meal'])
            ->whereHas(
                'order',
                function ($query) {
                    $query->whereDate('date', '>', today());
                }
            )
            ->simplePaginate(5, ['*'], 'future_meals_page');

        $futureOrders->fragment('future-meals');
        $futureOrders->appends('meals_page', $request->meals_page);
        $futureOrders->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()
            ->whereStatus(Deposit::STATUS_OK)
            ->latest()
            ->simplePaginate(5, ['*'], 'deposits_page');
        $deposits->fragment('deposit-history');
        $deposits->appends('meals_page', $request->meals_page);
        $deposits->appends('future_meals_page', $request->future_meals_page);

        return view('home', compact('balance', 'ordersHistory', 'todayOrders', 'futureOrders', 'deposits', 'user'));
    }
}
