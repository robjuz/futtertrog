<?php

namespace App\Http\Controllers;

use App\Deposit;
use App\Http\Requests\DepositDestroyRequest;
use App\Http\Requests\DepositRequest;
use App\User;
use Illuminate\Http\Request;

class DepositsController extends Controller
{
    public function __construct()
    {
        $this->middleware('cast.float')->only(['store']);
    }

    public function index(Request $request)
    {
        $deposits = Deposit::with('user')->latest()->paginate();

        if ($request->wantsJson()) {
            return $deposits;
        }

        return view('deposit.index', compact('deposits'));
    }

    public function create()
    {
        $users = User::all();

        return view('deposit.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DepositRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(DepositRequest $request)
    {
        Deposit::create($request->validated());

        return back()->with('success', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DepositDestroyRequest $request
     * @param Deposit $deposit
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(DepositDestroyRequest $request, Deposit $deposit)
    {
        $deposit->delete();

        return back();
    }
}
