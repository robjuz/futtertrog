<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Models\Meal;
use App\Models\OrderItem;
use App\Models\User;
use App\Rules\MealWithoutVariants;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AdminOrderItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('administerAny', OrderItem::class);

        return response()->json(OrderItem::with(['meal', 'user'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->input('user_id')))
            ->paginate());
    }

    public function create(Request $request): View
    {
        Gate::authorize('administerAny', OrderItem::class);

        if ($date = $request->query('date')) {
            $meals = Meal::forDate(Carbon::parse($date))->doesntHave('variants')->get();
            $users = User::orderBy('name')->get();

            return view('user_order.create', compact('meals', 'users'));
        }

        return view('user_order.select_date');
    }

    public function edit(OrderItem $orderItem): View
    {
        Gate::authorize('administer', $orderItem);

        $meals = Meal::forDate($orderItem->date)->doesntHave('variants')->get();
        $users = User::orderBy('name')->get();

        return view('user_order.edit', compact('orderItem', 'meals', 'users'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('administerAny', OrderItem::class);
        $data = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'meal_id' => ['required', 'exists:meals,id', new MealWithoutVariants()],
        ]);
        $meal = Meal::findOrFail($data['meal_id']);
        $recipient = User::findOrFail($data['user_id'] ?? $request->user()->id);
        Gate::authorize('orderFor', [OrderItem::class, $meal, $recipient]);

        $orderItem = $meal->order($recipient, $data['quantity'] ?? 1);
        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        return $request->wantsJson()
            ? response()->json($orderItem, 201)
            : back()->with('success', __('Success'));
    }

    public function update(Request $request, OrderItem $orderItem): JsonResponse|RedirectResponse
    {
        Gate::authorize('administer', $orderItem);
        $data = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'quantity' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'meal_id' => ['sometimes', 'exists:meals,id', new MealWithoutVariants()],
        ]);
        if (isset($data['user_id'])) {
            Gate::authorize('orderFor', [OrderItem::class,
                Meal::findOrFail($data['meal_id'] ?? $orderItem->meal_id), User::findOrFail($data['user_id'])]);
        }

        $orderItem->update($data);
        $orderItem->unsetRelation('user')->unsetRelation('meal');
        $orderItem->order->reopen();
        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        return $request->wantsJson()
            ? response()->json($orderItem)
            : redirect()->route('orders.edit', $orderItem->order)->with('success', __('Success'));
    }

    public function destroy(Request $request, OrderItem $orderItem): JsonResponse|RedirectResponse
    {
        Gate::authorize('administer', $orderItem);

        $orderItem->delete();
        $orderItem->order->reopen();
        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        return $request->wantsJson()
            ? response()->json(null, 204)
            : back()->with('success', __('Success'));
    }
}
