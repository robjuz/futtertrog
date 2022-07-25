<?php

namespace App\Http\Controllers;

use App\Order;
use App\Repositories\OrdersRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request, OrdersRepository $ordersRepository)
    {
        $this->authorize('list', Order::class);

        $from = $request->has('from') && !empty($request->from) ? $request->date('from') : null;
        $to = $request->has('to') && !empty($request->to) ? $request->date('to') : null;

        $orders = Order::query()
            ->withMin('meals', 'date')
            ->withMax('meals', 'date')
            ->whereHas(
                'orderItems.meal',
                fn(Builder $query) => $query->when(
                    $from,
                    fn(Builder $query) => $query->whereDate('date', '>=', $from->toDateString())
                )->when(
                    $to,
                    fn(Builder $query) => $query->whereDate('date', '<=', $to->toDateString())
                )
            )
            ->when(
                $request->input('user_id'),
                fn(Builder $query) => $query->whereRelation('orderItems', 'user_id', $request->input('user_id'))
            )
            ->when(
                $request->input('provider'),
                fn(Builder $query) => $query->whereRelation('orderItems', 'provider', $request->input('provider'))
            )
            ->when(
                $request->input('status'),
                fn(Builder $query) => $query->whereRelation('orderItems', 'status', $request->input('status'))
            )
            ->when(
                $request->filled('payed'),
                fn(Builder $query) => $request->input('payed')
                    ? $query->whereNotNull('payed_at')
                    : $query->whereNull('payed_at')
            )
            ->latest()
            ->paginate();

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        return view('order.index', compact('orders', 'from', 'to'));
    }

    public function edit(Order $order)
    {
        $order
            ->loadMin('meals', 'date')
            ->loadMax('meals', 'date');

        return view('order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Order $order
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
     * @param Request $request
     * @param \App\Order $order
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
