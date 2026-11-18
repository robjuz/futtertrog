<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Bookings;
use App\Http\Requests\DepositDestroyRequest;
use App\Http\Requests\DepositRequest;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DepositsController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('cast.float', only: ['store', 'update']),
        ];
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Deposit::class);

        $deposits = Deposit::with(['user' => function ($q) {
            $q->withTrashed();
        }])->latest()->paginate();

        if ($request->wantsJson()) {
            return $deposits;
        }

        return view('deposit.index', compact('deposits'));
    }

    public function create()
    {
        Gate::authorize('create', Deposit::class);
        $users = User::orderBy('name')->get();

        return view('deposit.create', compact('users'));
    }

    public function edit(Deposit $deposit)
    {
        Gate::authorize('update', $deposit);
        $users = User::orderBy('name')->withTrashed()->get();

        return view('deposit.edit', compact('users', 'deposit'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  DepositRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DepositRequest $request)
    {
        app(Bookings::class)->create($request->user(), $request->validated(), $request->header('Idempotency-Key'));

        return redirect()->route('deposits.index')->with('success', __('Success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  DepositRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(DepositRequest $request, Deposit $deposit)
    {
        app(Bookings::class)->update($request->user(), $deposit, $request->validated());

        return redirect()->route('deposits.index')->with('success', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DepositDestroyRequest  $request
     * @param  Deposit  $deposit
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function destroy(DepositDestroyRequest $request, Deposit $deposit)
    {
        app(Bookings::class)->delete($request->user(), $deposit);

        return back()->with('success', __('Success'));
    }
}
