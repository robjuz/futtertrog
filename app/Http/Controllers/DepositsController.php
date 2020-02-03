<?php

namespace App\Http\Controllers;

use App\Deposit;
use App\Http\Requests\DepositDestroyRequest;
use App\Http\Requests\DepositRequest;
use App\User;

class DepositsController extends Controller
{
    public function __construct()
    {
        $this->middleware('cast.float')->only(['store']);
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
