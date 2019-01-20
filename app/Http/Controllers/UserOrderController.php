<?php

namespace App\Http\Controllers;

use App\Events\OrderReopened;
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
                'date' => $meal->date
            ]);

            $order->meals()->syncWithoutDetaching($meal);

            if ($order->status === Order::STATUS_ORDERED) {
                $order->update([
                    'status' => Order::STATUS_OPEN
                ]);

                event(new OrderReopened($order, $user, $meal));
            }
        });

        if ($request->ajax()) {
            if ($request->wantsJson()) {
                return response(null, Response::HTTP_NO_CONTENT);
            }

            $orders = $user->meals()
                ->whereDate('date', $meal->date)
                ->get();

            return view('meal.meal', compact('meal', 'orders'));
        }

        return back();
    }

    public function destroy(Request $request, Meal $meal)
    {
        /** @var User $user */
        $user = $request->user();
        DB::transaction(function () use ($user, $meal) {
            $user->meals()->detach($meal);

            /** @var Order $order */
            $order = Order::firstOrCreate([
                'date' => $meal->date
            ]);

            $order->meals()->detach($meal);

            if ($order->status === Order::STATUS_ORDERED) {
                $order->update([
                    'status' => Order::STATUS_OPEN
                ]);

                event(new OrderReopened($order, $user, $meal));
            }

        });

        if ($request->ajax()) {
            if ($request->wantsJson()) {
                return response(null, Response::HTTP_NO_CONTENT);
            }

            $orders = $user->meals()
                ->whereDate('date', $meal->date)
                ->get();

            return view('meal.meal', compact('meal', 'orders'));
        }


        return back();
    }
}
