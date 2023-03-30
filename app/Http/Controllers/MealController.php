<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPossibility;
use App\Http\Requests\MealStoreRequest;
use App\Http\Requests\MealUpdateRequest;
use App\Meal;
use App\OrderItem;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use App\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
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
     *      description="Shows the meals for the current or given date",
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
     *          @OA\JsonContent(
     *              title="Meals",
     *              type="array",
     *              @OA\Items( type="object", ref="#/components/schemas/Meal" )
     *          ),
     *      ),
     * )
     */
    public function index(Request $request, OrdersRepository $orders, MealsRepository $meals)
    {
        /** @var User $user */
        $user = $request->user();

        if ($this->shouldRedirectToOtherDate($request)) {
            /** @var Meal $meal */
            $meal = Meal::where('date', '>', today())->orderBy('date')->first();

            return redirect()->route('meals.index', ['date' => $meal->date->toDateString()]);
        }

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

        $todayMeals->load('orderItems.order');

        return view(
            'meal.index',
            compact('todayMeals', 'requestedDate')
        );
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
//        abort(404);

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
            event(new NewOrderPossibility($meal->date));
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

    /**
     * @param Request $request
     * @return bool
     */
    private function shouldRedirectToOtherDate(Request $request): bool
    {
        return $request->missing('date')
            && ($request->user()->settings->redirectToNextDay ?? false)
            && ($request->user()->orderItems()->today()->exists() || Meal::where('date', today())->doesntExist())
            && Meal::where('date', '>', today())->exists();
    }
}
