<?php

namespace App\Http\Controllers;

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
        $todayOrders = $user->orderItems()->with('meal')
            ->whereHas('order', function ($query) {
                $query->whereDate('date', today());
            })->get();

        $ordersHistory = $user->orderItems()->with(['order', 'meal'])->latest()->paginate(5, ['*'], 'meals_page');
        $ordersHistory->appends('deposits_page', $request->deposits_page);
        $ordersHistory->appends('future_meals_page', $request->future_meals_page);

        $futureOrders = $user->orderItems()->with(['order', 'meal'])
            ->whereHas('order', function ($query) {
                $query->whereDate('date', '>', today());
            })->paginate(5, ['*'], 'future_meals_page');

        $futureOrders->appends('meals_page', $request->meals_page);
        $futureOrders->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()->latest()->paginate(5, ['*'], 'deposits_page');
        $deposits->appends('meals_page', $request->meals_page);
        $deposits->appends('future_meals_page', $request->future_meals_page);

        return view('home', compact('balance', 'ordersHistory', 'todayOrders', 'futureOrders', 'deposits', 'user'));
    }
}
