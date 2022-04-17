<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPossibility;
use App\Http\Requests\MealStoreRequest;
use App\Http\Requests\MealUpdateRequest;
use App\Meal;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use OpenApi\Annotations as OA;

class MealController extends Controller
{
    public function __construct()
    {
        $this->middleware('cast.float')->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param OrdersRepository $orders
     * @param MealsRepository $meals
     * @return Response
     *
     * @OA\Get(
     *      path="/api/order_possibilities",
     *      summary="Meals for given date",
     *      description="Schows the meals for the current or given date",
     *      operationId="meals.index",
     *      security={ {"bearer": {} }},
     *
     *      @OA\Parameter(
     *          name="date",
     *          in="query",
     *          @OA\Schema(type="string", format="date"),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Meals list",
     *          @OA\JsonContent(  ref="#/components/schemas/Meals" ),
     *      ),
     * )
     */
    public function index(Request $request, OrdersRepository $orders, MealsRepository $meals)
    {
        $requestedDate = Carbon::parse($request->query('date', today()));

        $todayMeals = Meal::forDate($requestedDate)
            ->whereNull('parent_id')
            ->byProvider($request->provider)
            ->with(['variants'])
            ->get()
            ->sortByPreferences();

        if ($request->wantsJson()) {
            return response()->json($todayMeals);
        }

        $todayOrders = $orders->userOrdersForDate($requestedDate, $request->user());

        return view('meal.index', compact('todayMeals', 'todayOrders', 'requestedDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Meal::class);

        return view('meal.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MealStoreRequest $request
     * @return Response
     */
    public function store(MealStoreRequest $request)
    {
        $meal = Meal::create($request->validated());

        if ($request->has('notify')) {
            event(new NewOrderPossibility($meal->date_from));
        }

        if ($request->wantsJson()) {
            return response()->json($meal, Response::HTTP_CREATED);
        }

        if ($request->has('saveAndNew')) {
            return redirect()->route('meals.create')->with('success', __('Saved'));
        }

        return redirect()->route('meals.index')->with('success', __('Saved'));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Meal $meal
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Meal $meal)
    {
        $this->authorize('view', $meal);

        if ($request->wantsJson()) {
            return response()->json($meal);
        }

        return view('meal.show', compact('meal'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Meal $meal
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function edit(Meal $meal)
    {
        $this->authorize('update', $meal);

        return view('meal.edit', compact('meal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param MealUpdateRequest $request
     * @param Meal $meal
     * @return Response
     */
    public function update(MealUpdateRequest $request, Meal $meal)
    {
        $meal->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json($meal);
        }

        return redirect()->route('meals.index')->with('success', __('Saved'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param Meal $meal
     * @return Response
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function destroy(Request $request, Meal $meal)
    {
        $this->authorize('delete', $meal);

        if ($meal->orderItems()->exists()) {
            abort(Response::HTTP_BAD_REQUEST, trans('futtertrog.meal_was_ordered'));
        }

        $meal->delete();

        if ($request->wantsJson()) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('meals.create')->with('success', __('Deleted'));
    }
}
