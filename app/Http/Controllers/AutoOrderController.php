<?php

namespace App\Http\Controllers;

use App\Actions\Admin\AutoOrder;
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

        app(AutoOrder::class)->execute($request->user(), $order);

        if ($request->wantsJson()) {
            return response()->json(null);
        }

        return back()->with('success', __('Success'));
    }
}
