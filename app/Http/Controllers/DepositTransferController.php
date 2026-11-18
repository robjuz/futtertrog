<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Bookings;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class DepositTransferController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('cast.float', only: ['store']),
        ];
    }

    public function create()
    {
        Gate::authorize('create', Deposit::class);
        $users = User::orderBy('name')->get();

        return view('deposit-transfer.create', compact('users'));
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'source' => 'required|exists:users,id',
            'target' => 'required|exists:users,id',
            'value' => 'required|numeric',
            'comment' => 'nullable|string',
        ]);

        Gate::authorize('transfer', [Deposit::class, User::findOrFail($request->source), User::findOrFail($request->target)]);

        app(Bookings::class)->transfer(
            $request->user(), $request->only(['source', 'target', 'value', 'comment']), $request->header('Idempotency-Key')
        );

        return back()->with('success', __('Success'));
    }
}
