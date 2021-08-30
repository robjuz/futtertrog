<?php

namespace App\Http\Controllers;

use App\Repositories\OrdersRepository;
use App\MealProviders\HolzkeMealProvider;
use Illuminate\Http\Request;

class HolzkeAutoOrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(
        Request            $request,
        OrdersRepository   $ordersRepository,
        HolzkeMealProvider $holzkeService)
    {
        $orders = $ordersRepository->get($request);

        $holzkeService->placeOrder($orders);

        if ($request->wantsJson()) {
            return response()->json(null);
        }

        return back()->with('success', __('Success'));
    }
}
