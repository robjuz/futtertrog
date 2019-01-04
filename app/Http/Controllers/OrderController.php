<?php

namespace App\Http\Controllers;

use App\Meal;
use App\Order;
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
     */
    public function index(Request $request)
    {
        $from = $request->from ?: today()->format('Y-m-d');
        $to = $request->to;

        $orders = Order::with('meals.users')
            ->whereDate('date', '>=', $from)
            ->when(!empty($to), function (Builder $query) use ($request) {
                $query->whereDate('date', '<=', $request->to);
            })
            ->orderBy('date')
            ->get();

        return view('order.index', compact('orders', 'from', 'to'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|string|max:30',
            'meals' => [
                'sometimes',
                'array',
                Rule::in(Meal::pluck('id'))
            ]
        ]);

        /** @var Order $order */
        $order = Order::create($request->only(['date', 'status']));
        $order->meals()->attach($request->only('meals'));

        if ($request->wantsJson()) {
            return response($order, Response::HTTP_CREATED);
        }

        return redirect()->route('orders.index')->with('message', __('Success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  \App\Order $order
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load('meals.users');

        if ($request->wantsJson()) {
            return response($order);
        }

        return view('order.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Order $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $order->update(
            $request->validate([
                'status' => ['sometimes','string', Rule::in(Order::$statuses)]
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
     * @param Request $request
     * @param  \App\Order $order
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

        return redirect()->route('orders.index');
    }
}
