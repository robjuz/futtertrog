<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AutoOrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return Response
     */
    public function __invoke(Request $request, Order $order) {

        Gate::authorize('autoOrder', $order);

        abort_unless($order->canBeAutoOrdered(), Response::HTTP_BAD_REQUEST, __('This order cannot be auto-ordered'));

        $order->autoOrder();


        if ($request->wantsJson()) {
            return response()->json(null);
        }

        return back()->with('success', __('Success'));
    }
}
