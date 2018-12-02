<?php

namespace App\Http\Controllers;

use App\Meal;
use App\Order;
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

    public function toggle(Request $request, Meal $meal)
    {
        DB::transaction(function () use ($request, $meal) {
            $request->user()->meals()->toggle($meal);

            /** @var Order $order */
            $order = Order::firstOrCreate(['date' => $meal->date]);

            if ($quantity = $meal->users()->count()) {

                $order->meals()->syncWithoutDetaching($meal);
                $order->meals()->updateExistingPivot($meal, [
                    'quantity' => $meal->users()->count()
                ]);
            } else {
                $order->meals()->detach($meal);
            }
        });

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back();
    }
}
