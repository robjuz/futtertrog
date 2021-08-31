<?php

namespace App\Http\Controllers;

use App\Repositories\OrdersRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutoOrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(
        Request          $request,
        OrdersRepository $ordersRepository
    )
    {
        $providerOrders = $ordersRepository->getByProvider($request);

        foreach ($providerOrders as $provider => $orders) {
            if ($provider = app($provider)->supportsAutoOrder()) {
                $provider->placeOrder($orders);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(null);
        }

        return back()->with('success', __('Success'));
    }
}
