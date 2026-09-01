<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OrdersRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, OrdersRepository $ordersRepository): JsonResponse|View
    {
        Gate::authorize('list', Order::class);

        $from = $request->has('from') && ! empty($request->from) ? $request->date('from') : null;
        $to = $request->has('to') && ! empty($request->to) ? $request->date('to') : null;

        $orders = Order::query()
            ->withMin('meals', 'date')
            ->withMax('meals', 'date')
            ->with('orderItems.meal')
            ->whereHas(
                'orderItems.meal',
                fn (Builder $query) => $query->when(
                    $from,
                    fn (Builder $query) => $query->whereDate('date', '>=', $from->toDateString())
                )->when(
                    $to,
                    fn (Builder $query) => $query->whereDate('date', '<=', $to->toDateString())
                )
            )
            ->when(
                $request->input('user_id'),
                fn (Builder $query) => $query->whereRelation('orderItems', 'user_id', $request->input('user_id'))
            )
            ->when(
                $request->input('provider'),
                fn (Builder $query) => $query->whereRelation('orderItems', 'provider', $request->input('provider'))
            )
            ->when(
                $request->input('status'),
                fn (Builder $query) => $query->whereRelation('orderItems', 'status', $request->input('status'))
            )
            ->when(
                $request->filled('payed'),
                fn (Builder $query) => $request->input('payed')
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

    public function edit(Order $order): View
    {
        $order
            ->loadMin('meals', 'date')
            ->loadMax('meals', 'date')
            ->load([
                'orderItems.meal',
                'orderItems.user' => fn ($q) => $q->withTrashed(),
            ]);

        $users = User::all();

        return view('order.edit', compact('order', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(Request $request, Order $order): JsonResponse|RedirectResponse|Response
    {
        Gate::authorize('update', $order);

        $order->update(
            $request->validate(
                [
                    'status' => ['sometimes', 'string', Rule::in(Order::$statuses)],
                    'payed_at' => ['date', 'sometimes', 'nullable'],
                    'user_id' => 'sometimes|nullable',
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
     * @throws \Exception
     * @throws AuthorizationException
     */
    public function destroy(Request $request, Order $order): Response|RedirectResponse
    {
        Gate::authorize('delete', $order);

        $order->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('orders.index')->with('success', __('Success'));
    }
}
