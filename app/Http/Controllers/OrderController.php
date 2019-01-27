<?php

namespace App\Http\Controllers;

use App\Events\OrderReopened;
use App\Meal;
use App\Order;
use App\OrderItem;
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
     */
    public function index(Request $request)
    {
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
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'date' => 'required|date',
            'user_id' => 'sometimes|exists:users,id',
            'quantity' => 'sometimes|numeric|min:1,max:10',
            'status' => 'sometimes|string|max:30',
        ];

        //only meals for the requested date are allowed
        $mealIds = Meal::whereDate('date_from', '<=', $request->date)->whereDate('date_to', '>=', $request->date)->pluck('id');
        $rules['meal_id'] = [
            'required',
            Rule::in($mealIds)
        ];

        $attributes = $request->validate($rules);

        /** @var User $user */
        $user = $request->user();

        //admin can create orders for other users;
        if ($user->is_admin) {
            $attributes['user_id'] = $attributes['user_id'] ?: $user->id;
        } else {
            $attributes['user_id'] = $user->id;
        }

        $meal = Meal::find($attributes['meal_id']);

        /** @var Order $order */
        $order = Order::query()->updateOrCreate([
            'date' => $attributes['date'],
            'provider' => $meal->provider
        ], [
            'status' => Order::STATUS_OPEN
        ]);

        $order->orderItems()->create([
            'meal_id' => $attributes['meal_id'],
            'user_id' => $attributes['user_id'],
            'quantity' => $attributes['quantity'],
        ]);

        if ($order->wasChanged()) {
            event(new OrderReopened($order, User::find($attributes['user_id']), $meal));
        }

        if ($request->wantsJson()) {
            return response($order, Response::HTTP_CREATED);
        }

        return back()->with('message', __('Success'));
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
     * @param  \App\OrderItem $order
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderItem $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Order               $order
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
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
     * @param  \App\OrderItem $order
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|Response
     * @throws \Exception
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Request $request, OrderItem $order)
    {
        $this->authorize('delete', $order);

        $order->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('orders.index');
    }
}
