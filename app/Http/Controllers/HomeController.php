<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderingData;
use App\Http\Resources\OwnOrderResource;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request): View|Response
    {

        /** @var User $user */
        $user = $request->user();

        $balance = $user->balance;

        $todayOrders = $user->orderHistory()->with('meal.parent')->today()->get();

        $ordersHistory = $user
            ->orderHistory()->with('meal.parent')
            ->simplePaginate(5, ['*'], 'meals_page')
            ->fragment('order-history')
            ->appends('deposits_page', $request->deposits_page)
            ->appends('future_meals_page', $request->future_meals_page);

        $futureOrders = $user
            ->futureOrders()->with(['order', 'meal.parent'])
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

        if (!config('user-interface.blade')) {
            return Inertia::render('Dashboard', [
                'balance' => (string) $balance,
                'todayOrders' => $todayOrders->map(fn ($item): array => (new OwnOrderResource($item))->resolve($request))->all(),
                'futureOrders' => OrderingData::orders($futureOrders, $request),
                'ordersHistory' => OrderingData::orders($ordersHistory, $request),
                'deposits' => OrderingData::deposits($deposits),
            ]);
        }

        return view('home', compact('balance', 'ordersHistory', 'todayOrders', 'futureOrders', 'deposits', 'user'));
    }
}
