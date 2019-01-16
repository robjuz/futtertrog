<?php

namespace App\Http\Controllers;

use App\Meal;
use App\Order;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UserOrderController extends Controller
{

    public function index(Request $request)
    {
        return $request->user()
            ->meals()
            ->whereDate('date', $request->query('date', today()))
            ->get();
    }

    public function store(Request $request, Meal $meal)
    {
        /** @var User $user */
        $user = $request->user();

        $quantity = $request->input('quantity') ?? 1;

        DB::transaction(function () use ($user, $meal, $quantity) {
            $user->meals()->attach($meal,  ['quantity' => $quantity, 'created_at' => now()]);

            /** @var Order $order */
            $order = Order::firstOrCreate([
                'date' => $meal->date,
                'status' => Order::STATUS_OPEN
            ]);

            if ($order->wasRecentlyCreated) {
                $order->meals()->attach($meal);
            }
        });

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back();
    }

    public function destroy(Request $request, Meal $meal)
    {
        /** @var User $user */
        $user = $request->user();
        $user->meals()->detach($meal);

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back();
    }
}
