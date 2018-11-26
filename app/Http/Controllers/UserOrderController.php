<?php

namespace App\Http\Controllers;

use App\Meal;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
        $request->user()->meals()->toggle($meal);


        /** @var Order $order */
        $order = Order::firstOrCreate([
            'date' => $meal->date
        ]);

        $order->meals()->syncWithoutDetaching($meal);

        $order->meals()->updateExistingPivot($meal, [
            'quantity' => $meal->users()->count()
        ]);

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back();
    }
}
