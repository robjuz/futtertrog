<?php

namespace App\Http\Controllers;

use App\Order;
use App\Repositories\OrdersRepository;
use App\User;
use Carbon\Carbon;
use Cknow\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, OrdersRepository $ordersRepository)
    {
        $this->authorize('list', Order::class);

        $orders = $ordersRepository->get($request);

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        $from = Carbon::parse($request->query('from', today()));

        $to = $request->has('to') && ! empty($request->to) ? Carbon::parse($request->to) : null;

        $sum = Money::sum(
            Money::parse(0),
            ...$orders->map->subtotal
        );

        $users = User::orderBy('name')->get();

        return view('order.index', compact('orders', 'from', 'to', 'sum', 'users'));
    }

    public function edit(Order $order)
    {
        return view('order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $order->update(
            $request->validate(
                [
                    'status' => ['sometimes', 'string', Rule::in(Order::$statuses)],
                    'payed_at' => ['date', 'sometimes', 'nullable']
                ]
            )
        );

        if ($request->wantsJson()) {
            return response($order, Response::HTTP_OK);
        }

        return back()->with('success', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|Response
     *
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        $order->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('orders.index')->with('success', __('Success'));
    }
}
