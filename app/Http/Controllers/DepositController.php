<?php

namespace App\Http\Controllers;

use App\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepositController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Deposit::class);

        $deposit = Deposit::create($request->validate([
            'user_id' => 'required',
            'value' => 'required|numeric'
        ]));

        if ($request->wantsJson()) {
            return response($deposit, Response::HTTP_CREATED);
        }

        return back()->with('message', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Deposit $deposit
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Deposit $deposit)
    {
        $this->authorize('delete', $deposit);
        $deposit->delete();

        return back();
    }
}
