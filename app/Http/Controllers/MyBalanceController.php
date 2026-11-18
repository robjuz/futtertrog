<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyBalanceController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $balance = $user->balance;
        $deposits = $user->deposits()->whereStatus(Deposit::STATUS_OK)->latest()->paginate();

        return view('my.balance', compact('user', 'balance', 'deposits'));
    }
}
