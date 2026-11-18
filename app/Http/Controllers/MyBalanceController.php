<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Http\Resources\OrderingData;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyBalanceController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        $user = $request->user();
        $balance = $user->balance;
        $deposits = $user->deposits()->whereStatus(Deposit::STATUS_OK)->latest()->paginate()->withQueryString();

        if (!config('user-interface.blade')) {
            return Inertia::render('My/Balance', [
                'balance' => (string) $balance,
                'deposits' => OrderingData::deposits($deposits),
                'orders' => OrderingData::orders($user->orderHistory()->with('meal.parent')->paginate(15, ['*'], 'orders_page')->withQueryString(), $request),
            ]);
        }

        return view('my.balance', compact('user', 'balance', 'deposits'));
    }
}
