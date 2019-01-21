<?php

namespace App\Http\Controllers;

use App\Events\OrderReopened;
use App\Meal;
use App\Order;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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

    public function create(Request $request)
    {
        if ($date =  $request->query('date')) {
            $date = Carbon::parse($date);
            $meals = Meal::whereDate('date', $date)->get();
            $users = User::all();
            return view('user_order.create', compact('meals', 'users', 'date'));
        }

        return view('user_order.select_date');

    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'meal_id' => ['required', 'exists:meals,id'],
            'quantity' => ['sometimes', 'numeric' ,'min:1']
        ]);

        $quantity = $request->input('quantity', 1);
        $user = $request->user()->is_admin ? User::findOrFail($request->input('user_id')) : $request->user();
        $meal = Meal::findOrFail($request->input('meal_id'));

        DB::transaction(function () use ($user, $meal, $quantity) {
            if ($user->meals()->whereKey($meal)->exists()) {
                $user->meals()->updateExistingPivot($meal, ['quantity' => $quantity]);
            } else {
                $user->meals()->attach($meal, ['quantity' => $quantity, 'created_at' => now()]);
            }

            /** @var Order $order */
            $order = Order::firstOrCreate([
                'date' => $meal->date,
                'provider' => $meal->provider
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

        return back()->with('success', trans('Saved'));
    }

    public function destroy(Request $request, Meal $meal)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = $request->user()->is_admin ? User::findOrFail($request->input('user_id')) : $request->user();

        DB::transaction(function () use ($user, $meal) {
            $user->meals()->detach($meal);

            /** @var Order $order */
            $order = Order::firstOrCreate([
                'date' => $meal->date,
                'provider' => $meal->provider
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
