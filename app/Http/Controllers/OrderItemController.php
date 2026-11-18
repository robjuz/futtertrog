<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

use App\Events\OrderUpdated;
use App\Http\Resources\OrderingData;
use App\Http\Resources\OwnOrderResource;
use Inertia\Inertia;
use App\Models\Meal;
use App\Models\OrderItem;
use App\Rules\MealWithoutVariants;
use App\Rules\MealInOrderGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     *
     * @throws AuthorizationException
     */
    #[OA\Get(
        path: "/api/orders",
        summary: "List of orders",
        description: "List of orders for today and upcoming days",
        operationId: "order_items.index",
        security: [["bearer" => []]],
        responses: [new OA\Response(
            response: 200,
            description: "Success",
            content: new OA\JsonContent(
                ref: "#/components/schemas/OrderItem"
            )
        )]
    )]
    public function index(Request $request): View|JsonResponse|\Inertia\Response
    {
        Gate::authorize('list', OrderItem::class);

        $orders = $request->user()->orderItems()->with(['meal.parent', 'order'])->latest()->paginate();

        if ($request->routeIs('my.orders')) {
            if (!config('user-interface.blade')) {
                return Inertia::render('My/Orders', ['orders' => OrderingData::orders($orders, $request), 'calendarUrl' => route('meals.ical', absolute: false)]);
            }
            return view('my.orders', compact('orders'));
        }

        return response()->json(OrderingData::orders($orders, $request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    #[OA\Post(
        path: "/api/place_order",
        summary: "Place order",
        description: "Order a given meal for a given date",
        operationId: "order_items.store",
        security: [["bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Pass user credentials",
            content: new OA\JsonContent(
                required: ["date", "meal_id"],
                properties: [new OA\Property(
                    property: "date",
                    type: "string",
                    format: "date",
                    example: "2020-02-02"
                ), new OA\Property(
                    property: "quantity",
                    type: "integer",
                    example: "1",
                    minimum: 1
                ), new OA\Property(
                    property: "meal_id",
                    ref: "#/components/schemas/id"
                )]
            )
        ),
        responses: [new OA\Response(
            response: 200,
            description: "Success",
            content: new OA\JsonContent(
                ref: "#/components/schemas/Meal"
            )
        )]
    )]
    public function store(Request $request): View|RedirectResponse|JsonResponse
    {
        $request->validate(
            [
                'quantity' => 'sometimes|integer|min:1|max:10',
                'meal_id' => ['required', 'exists:meals,id', new MealWithoutVariants()],
            ]
        );

        /** @var Meal $meal */
        $meal = Meal::findOrFail($request->input('meal_id'));

        Gate::authorize('create', [OrderItem::class, $meal->date]);

        $orderItem = $meal->order(
            $request->user()->id,
            $request->input('quantity', 1)
        );

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json((new OwnOrderResource($orderItem->load(['meal.parent', 'order'])))->resolve($request), Response::HTTP_CREATED);
        }

        if ($request->ajax()) {
            return view('meal.meal', compact('meal'));
        }

        return back()->with('success', __('Success'));
    }


    public function update(Request $request, OrderItem $orderItem): View|JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $orderItem);

        $data = $request->validate(
            [
                'quantity' => 'sometimes|integer|min:0|max:10',
                'meal_id' => ['sometimes', 'exists:meals,id', new MealWithoutVariants()],
            ]
        );

        if (isset($data['meal_id'])) {
            Gate::authorize('create', [OrderItem::class, Meal::findOrFail($data['meal_id'])->date]);
            $request->validate(['meal_id' => [new MealInOrderGroup($orderItem->order)]]);
        }

        $orderItem->update($data);
        $orderItem->unsetRelation('meal');
        $orderItem->order->reopen();

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json((new OwnOrderResource($orderItem->load(['meal.parent', 'order'])))->resolve($request), Response::HTTP_OK);
        }

        if ($request->ajax()) {

            $meal = $orderItem->meal;
            return view('meal.meal', compact('meal'));
        }

        return redirect()->route('my.orders')->with('success', __('Success'));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, OrderItem $orderItem): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $orderItem);

        $orderItem->delete();
        $orderItem->order->reopen();

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return back(Response::HTTP_FOUND, [], route('order_items.index'))->with('success', __('Success'));
    }
}
