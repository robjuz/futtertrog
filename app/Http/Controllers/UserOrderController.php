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

    public function toggle(Request $request, Meal $meal)
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user, $meal) {
            $changes = $user->meals()->toggle($meal, false);

            foreach ($changes['attached'] as $attached) {
                $user->meals()->updateExistingPivot($attached, ['created_at' => now()]);
            }

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
