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
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        /** @var User $user */
        $user = $request->user();

        $balance = $user->balance;

        $todayOrders = $user->orderHistory()->today()->get();

        $ordersHistory = $user
            ->orderHistory()
            ->simplePaginate(5, ['*'], 'meals_page')
            ->fragment('order-history')
            ->appends('deposits_page', $request->deposits_page)
            ->appends('future_meals_page', $request->future_meals_page);

        $futureOrders = $user
            ->futureOrders()
            ->simplePaginate(5, ['*'], 'future_meals_page')
            ->fragment('future-meals')
            ->appends('meals_page', $request->meals_page)
            ->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()
            ->whereStatus(Deposit::STATUS_OK)
            ->simplePaginate(5, ['*'], 'deposits_page')
            ->fragment('deposit-history')
            ->appends('meals_page', $request->meals_page)
            ->appends('future_meals_page', $request->future_meals_page);

        return view('home', compact('balance', 'ordersHistory', 'todayOrders', 'futureOrders', 'deposits', 'user'));
    }
}
