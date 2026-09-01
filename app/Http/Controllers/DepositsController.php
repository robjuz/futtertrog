<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositDestroyRequest;
use App\Http\Requests\DepositRequest;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DepositsController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('cast.float', only: ['store', 'update']),
        ];
    }

    /** @return LengthAwarePaginator<int, Deposit> */
    public function index(Request $request): View|LengthAwarePaginator
    {
        $deposits = Deposit::with(['user' => function ($q) {
            $q->withTrashed();
        }])->latest()->paginate();

        if ($request->wantsJson()) {
            return $deposits;
        }

        return view('deposit.index', compact('deposits'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get();

        return view('deposit.create', compact('users'));
    }

    public function edit(Deposit $deposit): View
    {
        $users = User::orderBy('name')->withTrashed()->get();

        return view('deposit.edit', compact('users', 'deposit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DepositRequest $request): RedirectResponse
    {
        Deposit::create($request->validated());

        return redirect()->route('deposits.index')->with('success', __('Success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(DepositRequest $request, Deposit $deposit): RedirectResponse
    {
        $deposit->update($request->validated());

        return redirect()->route('deposits.index')->with('success', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws \Exception
     */
    public function destroy(DepositDestroyRequest $request, Deposit $deposit): RedirectResponse
    {
        $deposit->delete();

        return back()->with('success', __('Success'));
    }
}
