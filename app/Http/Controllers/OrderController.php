<?php

namespace App\Http\Controllers;

use App\Order;
use App\OrderItem;
use Carbon\Carbon;
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
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('list', Order::class);

        $from = Carbon::parse($request->query('from', today()));
        $to = $request->has('to') && !empty($request->to) ? Carbon::parse($request->to) : null;

        $orders = Order::with(['orderItems.meal', 'orderItems.user'])
            ->whereDate('date', '>=', $from->toDateString())
            ->when(!empty($to), function (Builder $query) use ($to) {
                $query->whereDate('date', '<=', $to->toDateString());
            })
            ->orderBy('date')
            ->get();

        $sum = $orders->sum(function ($order) {
            return $order->orderItems->sum(function ($order) {
                return $order->meal->price * $order->quantity;
            });
        });

        return view('order.index', compact('orders', 'from', 'to', 'sum'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Order::class);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Order::class);
    }

    /**
     * Display the specified resource.
     *
     * @param Request         $request
     * @param  \App\OrderItem $order
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, OrderItem $order)
    {
        $this->authorize('view', $order);

        $order->load(['orderItems.meal', 'orderItems.user']);

        if ($request->wantsJson()) {
            return response($order);
        }

        return view('order.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param OrderItem $orderItem
     *
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(OrderItem $orderItem)
    {
        $this->authorize('update', $orderItem);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Order                     $orderItem
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Order $orderItem)
    {
        $this->authorize('update', $orderItem);

        $order->update(
            $request->validate([
                'status' => ['sometimes', 'string', Rule::in(OrderItem::$statuses)]
            ])
        );

        if ($request->wantsJson()) {
            return response($order, Response::HTTP_OK);
        }

        return back()->with('message', __('Success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request         $request
     * @param  \App\OrderItem $orderItem
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|Response
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, OrderItem $orderItem)
    {
        $this->authorize('delete', $orderItem);


        $orderItem->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }


        return back()->with('message', __('Success'));
    }
}
