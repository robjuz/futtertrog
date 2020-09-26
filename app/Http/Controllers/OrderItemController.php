<?php

namespace App\Http\Controllers;

use App\Events\OrderReopened;
use App\Meal;
use App\Order;
use App\OrderItem;
use App\Services\HolzkeService;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('list', OrderItem::class);

        /** @var \App\User $user */
        $user = $request->user();
        if ($user->is_admin) {
            $query = OrderItem::with(['meal', 'user'])
                ->when(
                    $request->has('user_id'),
                    function (Builder $query) use ($request) {
                        $query->where('user_id', $request->query('user_id'));
                    }
                );
        } else {
            $query = $user->orderItems()->with(['meal']);
        }

        return response()->json($query->paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request)
    {
        $this->authorize('create', OrderItem::class);

        if ($date = $request->query('date')) {
            $date = Carbon::parse($date);
            $meals = Meal::whereDate('date_from', '>=', $date)
                ->whereDate('date_to', '<=', $date)
                ->get();
            $users = User::orderBy('name')->get();

            return view('user_order.create', compact('meals', 'users', 'date'));
        }

        return view('user_order.select_date');
    }

    /**
     * Show the form for editing a new resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @param \App\OrderItem $orderItem
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(OrderItem $orderItem)
    {
        $this->authorize('edit', $orderItem);

        $date = $orderItem->order->date;

        $meals = Meal::whereDate('date_from', '>=', $date)
            ->whereDate('date_to', '<=', $date)
            ->get();
        $users = User::orderBy('name')->get();

        return view('user_order.edit', compact('orderItem', 'meals', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $attributes = $request->validate(
            [
                'date' => 'required|date',
                'user_id' => 'sometimes|exists:users,id',
                'quantity' => 'sometimes|numeric|min:1,max:10',
                'meal_id' => 'required|exists:meals,id',
            ]
        );

        $this->authorize('create', [OrderItem::class, $request->date]);

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
        $order = Order::query()
            ->updateOrCreate(
                [
                    'date' => $attributes['date'],
                    'provider' => $meal->provider,
                ],
                [
                    'status' => Order::STATUS_OPEN,
                ]
            );

        $orderItem = $order->orderItems()
            ->create(
                [
                    'meal_id' => $attributes['meal_id'],
                    'user_id' => $attributes['user_id'],
                    'quantity' => $attributes['quantity'] ?? 1,
                ]
            );

        if ($order->wasChanged()) {
            event(new OrderReopened($order, $orderItem->user, $meal));
        }

        if ($request->wantsJson()) {
            return response($orderItem, Response::HTTP_CREATED);
        }

        return back()->with('success', __('Success'));
    }

    public function update(Request $request, OrderItem $orderItem, HolzkeService $holzkeService)
    {
        $data = $request->validate(
            [
                'user_id' => 'sometimes|exists:users,id',
                'quantity' => 'sometimes|numeric|min:1,max:10',
                'meal_id' => 'required|exists:meals,id',
            ]
        );

        DB::transaction(
            function () use ($orderItem, $holzkeService, $data) {
                $orderItem->update($data);

                $holzkeService->updateOrder($orderItem);
            }
        );


        if ($request->wantsJson()) {
            return response($orderItem, Response::HTTP_OK);
        }

        return redirect()->route('orders.edit', $orderItem->order)->with('success', __('Success'));
    }

    public function destroy(Request $request, OrderItem $orderItem, HolzkeService $holzkeService)
    {
        $this->authorize('delete', $orderItem);

        $order = $orderItem->order;
        $order->update(
            [
                'status' => Order::STATUS_OPEN,
            ]
        );

//        if ($order->wasChanged()) {
//            event(new OrderReopened($order, $orderItem->user, $orderItem->meal));
//        }

        DB::transaction(
            function () use ($orderItem, $holzkeService) {
                $orderItem->delete();

                $holzkeService->updateOrder($orderItem);
            }
        );


        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back(Response::HTTP_FOUND, [], route('order_items.index'))->with('success', __('Success'));
    }
}
