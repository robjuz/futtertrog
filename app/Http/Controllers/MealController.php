<?php

namespace App\Http\Controllers;

use App\Events\NewOrderPossibility;
use App\Http\Requests\MealStoreRequest;
use App\Http\Requests\MealUpdateRequest;
use App\Models\Meal;
use App\Models\User;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MealController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('cast.float', only: ['store']),
        ];
    }

    /**
     * Display a listing of the resource.
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
     *
     *          @OA\Schema(type="string", format="date"),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Meals list",
     *
     *          @OA\JsonContent(
     *              title="Meals",
     *              type="array",
     *
     *              @OA\Items( type="object", ref="#/components/schemas/Meal" )
     *          ),
     *      ),
     * )
     */
    public function index(Request $request, OrdersRepository $orders, MealsRepository $meals): RedirectResponse|JsonResponse|View
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
     * @throws AuthorizationException
     */
    public function create(): View
    {
        //        abort(404);

        Gate::authorize('create', Meal::class);

        return view('meal.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *      path="/api/meals",
     *      summary="Create meal",
     *      description="Create a new meal",
     *      operationId="meals.store",
     *      security={ {"bearer": {} }},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass meal properties",
     *
     *          @OA\JsonContent(
     *              required={"name","date"},
     *
     *              @OA\Property( property="name", type="string", example="Pizza" ),
     *              @OA\Property( property="date", type="string", format="date", example="2020-02-02" ),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *
     *          @OA\JsonContent( ref="#/components/schemas/Meal" ),
     *      ),
     * )
     */
    public function store(MealStoreRequest $request): JsonResponse|RedirectResponse
    {
        $meal = Meal::create($request->validated());

        if ($request->has('notify')) {
            event(new NewOrderPossibility(Carbon::instance($meal->date)));
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
     * @OA\Get(
     *      path="/api/meals/{meal}",
     *      summary="Show meal",
     *      description="Get a single meal",
     *      operationId="meals.show",
     *      security={ {"bearer": {} }},
     *
     *      @OA\Parameter(
     *          name="meal",
     *          in="path",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Meal",
     *
     *          @OA\JsonContent( ref="#/components/schemas/Meal" ),
     *      ),
     * )
     */
    public function show(Request $request, Meal $meal): JsonResponse|View
    {
        Gate::authorize('view', $meal);

        if ($request->wantsJson()) {
            return response()->json($meal);
        }

        return view('meal.show', compact('meal'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     */
    public function edit(Meal $meal): View
    {
        Gate::authorize('update', $meal);

        return view('meal.edit', compact('meal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *      path="/api/meals/{meal}",
     *      summary="Update meal",
     *      description="Update a meal",
     *      operationId="meals.update",
     *      security={ {"bearer": {} }},
     *
     *      @OA\Parameter(
     *          name="meal",
     *          in="path",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *      ),
     *
     *      @OA\RequestBody(
     *          required=true,
     *          description="Pass meal properties",
     *
     *          @OA\JsonContent(
     *              required={"name","date"},
     *
     *              @OA\Property( property="name", type="string", example="Pizza" ),
     *              @OA\Property( property="date", type="string", format="date", example="2020-02-02" ),
     *          ),
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Updated",
     *
     *          @OA\JsonContent( ref="#/components/schemas/Meal" ),
     *      ),
     * )
     */
    public function update(MealUpdateRequest $request, Meal $meal): JsonResponse|RedirectResponse
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
     * @OA\Delete(
     *      path="/api/meals/{meal}",
     *      summary="Delete meal",
     *      description="Delete a meal",
     *      operationId="meals.destroy",
     *      security={ {"bearer": {} }},
     *
     *      @OA\Parameter(
     *          name="meal",
     *          in="path",
     *          required=true,
     *
     *          @OA\Schema(type="integer"),
     *      ),
     *
     *      @OA\Response(
     *          response=204,
     *          description="Deleted",
     *      ),
     * )
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function destroy(Request $request, Meal $meal): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $meal);

        if ($meal->orderItems()->exists()) {
            abort(Response::HTTP_BAD_REQUEST, trans('futtertrog.meal_was_ordered'));
        }

        $meal->delete();

        if ($request->wantsJson()) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('meals.create')->with('success', __('Deleted'));
    }

    private function shouldRedirectToOtherDate(Request $request): bool
    {
        return $request->missing('date')
            && ($request->user()->settings->redirectToNextDay ?? false)
            && ($request->user()->orderItems()->today()->exists() || Meal::where('date', today())->doesntExist())
            && Meal::where('date', '>', today())->exists();
    }
}
