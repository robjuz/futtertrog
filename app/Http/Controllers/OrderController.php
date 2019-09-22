<?php

namespace App\Http\Controllers;

use App\Order;
use App\User;
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
        $to = $request->has('to') && ! empty($request->to) ? Carbon::parse($request->to) : null;

        $orders = Order::with(['orderItems.meal'])
            ->whereHas('orderItems.meal')
            ->whereDate('date', '>=', $from->toDateString())
            ->when(
                ! empty($to),
                function (Builder $query) use ($to) {
                    $query->whereDate('date', '<=', $to->toDateString());
                }
            )
            ->when(
                $request->input('user_id', null),
                function (Builder $query) use ($request) {
                    $query->with(
                        [
                            'orderItems' => function ($query) use ($request) {
                                $query->whereUserId($request->user_id);
                            },
                            'orderItems.user',
                        ]
                    );
                    $query->whereHas(
                        'orderItems',
                        function (Builder $query) use ($request) {
                            $query->whereUserId($request->user_id);
                        }
                    );
                },
                function (Builder $query) {
                    $query->with(['orderItems.user']);
                }
            )
            ->orderBy('date')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($orders);
        }

        $sum = $orders->sum->subtotal;

        $users = User::all();

        return view('order.index', compact('orders', 'from', 'to', 'sum', 'users'));
    }

    public function edit(Order $order)
    {
        return view('order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Order $order
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $order->update(
            $request->validate(
                [
                    'status' => ['sometimes', 'string', Rule::in(Order::$statuses)],
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
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|Response
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

        return back()->with('success', __('Success'));
    }
}
