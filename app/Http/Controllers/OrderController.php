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
     */
    public function index(Request $request)
    {
        $from = Carbon::parse($request->query('from', today()));
        $to = $request->has('to') && !empty($request->to) ? Carbon::parse($request->to) : null;

        $orders = Order::with('meals.users')
            ->whereHas('meals')
            ->whereDate('date', '>=', $from->toDateString())
            ->when(!empty($to), function (Builder $query) use ($to) {
                $query->whereDate('date', '<=', $to->toDateString());
            })
            ->orderBy('date')
            ->get();

        $sum = $orders->sum(function ($order) {
            return $order->meals->sum(function ($meal) {
                return $meal->price * $meal->users->sum(function ($user) {
                        return $user->pivot->quantity;
                    });
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
        $attributes = $request->validate([
            'date' => 'required|date',
            'meal_id' => 'required|exists:meals,id',
            'user_id' => 'sometimes|exists:users,id',
            'quantity' => 'sometimes|numeric|min:1,max:10',
            'status' => 'sometimes|string|max:30',

        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->is_admin) {
            $attributes['user_id'] = $attributes['user_id'] ?: $user->id;
        } else {
            $attributes['user_id'] =  $user->id;
        }

        /** @var Order $order */
        $order = Order::create($attributes);

        if ($request->wantsJson()) {
            return response($order, Response::HTTP_CREATED);
        }

        return back()->with('message', __('Success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Request     $request
     * @param  \App\Order $order
     *
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
     *
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
     * @param  \App\Order               $order
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $order->update(
            $request->validate([
                'status' => ['sometimes', 'string', Rule::in(Order::$statuses)]
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
     * @param Request     $request
     * @param  \App\Order $order
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

        return redirect()->route('orders.index');
    }
}
