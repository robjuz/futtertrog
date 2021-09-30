<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Meal;
use App\Order;
use App\OrderItem;
use App\Rules\MealWithoutVariants;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request)
    {
        $this->authorize('create', OrderItem::class);

        if ($date = $request->query('date')) {
            $date = Carbon::parse($date);
            $meals = Meal::whereDate('date_from', '>=', $date)
                ->whereDate('date_to', '<=', $date)
                ->doesntHave('variants')
                ->get();
            $users = User::orderBy('name')->get();

            return view('user_order.create', compact('meals', 'users', 'date'));
        }

        return view('user_order.select_date');
    }

    /**
     * Show the form for editing a new resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     *
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Post (
     *      path="/api/place_order",
     *      summary="Place order",
     *      description="Order a given meal for a given date",
     *      operationId="order_items.store",
     *      security={ {"bearer": {} }},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass user credentials",
     *          @OA\JsonContent(
     *              required={"date","meal_id"},
     *              @OA\Property( property="date", type="string", format="date", example="2020-02-02" ),
     *              @OA\Property( property="quantity", type="integer", example="1", minimum="1" ),
     *              @OA\Property( property="meal_id", ref="#/components/schemas/id" ),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent( ref="#/components/schemas/Meal" ),
     *      ),
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate(
            [
                'date' => 'required|date',
                'user_id' => 'sometimes|exists:users,id',
                'quantity' => 'sometimes|numeric|min:1,max:10',
                'meal_id' => ['required','exists:meals,id', new MealWithoutVariants()],
            ]
        );

        $this->authorize('create', [OrderItem::class, $request->date]);

        /** @var User $user */
        $userId = Auth::id();

        //admin can create orders for other users;
        if (Auth::user()->is_admin and $request->has('user_id')) {
            $userId = $request->input('user_id');
        }

        $meal = Meal::find($attributes['meal_id']);

        $orderItem = $meal->order(
            $userId,
            $request->input('date'),
            $request->input('quantity', 1)
        );

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response($orderItem, Response::HTTP_CREATED);
        }

        return back()->with('success', __('Success'));
    }

    public function update(Request $request, OrderItem $orderItem)
    {
        $data = $request->validate(
            [
                'user_id' => 'sometimes|exists:users,id',
                'quantity' => 'sometimes|numeric|min:1,max:10',
                'meal_id' => 'required|exists:meals,id',
            ]
        );

        $orderItem->update($data);

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response($orderItem, Response::HTTP_OK);
        }

        return redirect()->route('orders.edit', $orderItem->order)->with('success', __('Success'));
    }

    public function destroy(Request $request, OrderItem $orderItem)
    {
        $this->authorize('delete', $orderItem);

        $orderItem->delete();

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return back(Response::HTTP_FOUND, [], route('order_items.index'))->with('success', __('Success'));
    }
}
