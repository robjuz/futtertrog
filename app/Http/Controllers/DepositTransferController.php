<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Deposit;
use Illuminate\Support\Facades\DB;
use App\User;

class DepositTransferController extends Controller
{

    public function show()
    {
        $users = User::all();

        return view('user/new-transfer', compact('users'));
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
            'comment' => 'nullable|string'
        ]);

        DB::transaction(function() use ($request) {
            Deposit::create([
                'user_id' => $request->source,
                'value' => (-1) * $request->value,
                'comment' => $request->comment
            ]);
    
            Deposit::create([
                'user_id' => $request->target,
                'value' =>  $request->value,
                'comment' => $request->comment
            ]);
        });

        return back()->with('success', __('Success'));
    }
}
