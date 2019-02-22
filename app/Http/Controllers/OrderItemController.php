<?php

namespace App\Http\Controllers;

use App\Events\OrderReopened;
use App\Meal;
use App\Order;
use App\OrderItem;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
            $query = OrderItem::with(['meal', 'user'])->when($request->has('user_id'), function (Builder $query) use (
                    $request
                ) {
                    $query->where('user_id', $request->query('user_id'));
                });
        } else {
            $query = $user->orderItems()->with(['meal']);
        }

        return response()->json($query->paginate());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request)
    {
        $this->authorize('create', OrderItem::class);

        if ($date = $request->query('date')) {
            $date = Carbon::parse($date);
            $meals = Meal::whereDate('date_from', '>=', $date)->whereDate('date_to', '<=', $date)->get();
            $users = User::all();

            return view('user_order.create', compact('meals', 'users', 'date'));
        }

        return view('user_order.select_date');
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
        $this->authorize('create', OrderItem::class);

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
            Rule::in($mealIds),
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
            'provider' => $meal->provider,
        ], [
            'status' => Order::STATUS_OPEN,
        ]);

        $orderItem = $order->orderItems()->create([
            'meal_id' => $attributes['meal_id'],
            'user_id' => $attributes['user_id'],
            'quantity' => $attributes['quantity'] ?? 1,
        ]);

        if ($order->wasChanged()) {
            event(new OrderReopened($order, User::find($attributes['user_id']), $meal));
        }

        if ($request->wantsJson()) {
            return response($orderItem, Response::HTTP_CREATED);
        }

        return back()->with('success', __('Success'));
    }

    public function destroy(Request $request, OrderItem $orderItem)
    {
        $this->authorize('delete', $orderItem);

        $orderItem->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back(Response::HTTP_FOUND, [], route('order_items.index'))->with('success', __('Success'));
    }
}
