<?php

namespace App\Http\Controllers;

use App\Events\OrderUpdated;
use App\Meal;
use App\OrderItem;
use App\Rules\MealWithoutVariants;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     *
     * @throws AuthorizationException
     * @OA\Get (
     *      path="/api/orders",
     *      summary="List of orders",
     *      description="List of orders for today and upcoming days",
     *      operationId="order_items.index",
     *      security={ {"bearer": {} }},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent( ref="#/components/schemas/OrderItem" ),
     *      ),
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('list', OrderItem::class);

        /** @var User $user */
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
     * @param Request $request
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function create(Request $request)
    {
        $this->authorize('create', OrderItem::class);

        if ($date = $request->query('date')) {
            $meals = Meal::forDate(Carbon::parse($date))->doesntHave('variants')->get();

            $users = User::orderBy('name')->get();

            return view('user_order.create', compact('meals', 'users'));
        }

        return view('user_order.select_date');
    }

    /**
     * Show the form for editing a new resource.
     *
     * @param Request $request
     * @param OrderItem $orderItem
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function edit(OrderItem $orderItem)
    {
        $this->authorize('edit', $orderItem);

        $meals = Meal::forDate($orderItem->date)
            ->doesntHave('variants')
            ->get();

        $users = User::orderBy('name')->get();

        return view('user_order.edit', compact('orderItem', 'meals', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
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
    public function store(Request $request): View|RedirectResponse|JsonResponse
    {
        $request->validate(
            [
                'user_id' => 'sometimes|exists:users,id',
                'quantity' => 'sometimes|numeric|min:1,max:10',
                'meal_id' => ['required', 'exists:meals,id', new MealWithoutVariants()],
            ]
        );

        $meal = Meal::findOrFail($request->input('meal_id'));

        $this->authorize('create', [OrderItem::class, $meal->date]);

        /** @var User $user */
        $userId = Auth::id();

        //admin can create orders for other users;
        if (Auth::user()->is_admin and $request->has('user_id')) {
            $userId = $request->input('user_id');
        }

        $orderItem = $meal->order(
            $userId,
            $request->input('quantity', 1)
        );

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json($orderItem, Response::HTTP_CREATED);
        }

        if ($request->ajax()) {
            return view('meal.meal', compact('meal'));
        }

        return back()->with('success', __('Success'));
    }


    public function update(Request $request, OrderItem $orderItem): View|JsonResponse|RedirectResponse
    {
        $data = $request->validate(
            [
                'user_id' => Rule::when(Auth::user()->is_admin, 'sometimes|exists:users,id'),
                'quantity' => 'sometimes|numeric|min:0,max:10',
                'meal_id' => 'required|exists:meals,id',
            ]
        );

        $orderItem->update($data);

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json($orderItem, Response::HTTP_OK);
        }

        if ($request->ajax()) {

            $meal = $orderItem->meal;
            return view('meal.meal', compact('meal'));
        }

        return redirect()->route('orders.edit', $orderItem->order)->with('success', __('Success'));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, OrderItem $orderItem): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $orderItem);

        $orderItem->delete();

        event(new OrderUpdated($orderItem->order, $orderItem->user, $orderItem));

        if ($request->wantsJson()) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return back(Response::HTTP_FOUND, [], route('order_items.index'))->with('success', __('Success'));
    }
}
