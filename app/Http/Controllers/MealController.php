<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

use App\Events\NewOrderPossibility;
use App\Http\Requests\MealStoreRequest;
use App\Models\Meal;
use App\Models\User;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MealController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [new \Illuminate\Routing\Controllers\Middleware('cast.float', only: ['store'])];
    }

    public function create()
    {
//        abort(404);

        Gate::authorize('create', Meal::class);

        return view('meal.create');
    }

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

        return redirect()->route('admin.meals.index')->with('success', __('Saved'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param OrdersRepository $orders
     * @param MealsRepository $meals
     * @return Response
     */
    #[OA\Get(
        path: "/api/order_possibilities",
        summary: "Meals for given date",
        description: "Shows the meals for the current or given date",
        operationId: "meals.index",
        security: [["bearer" => []]],
        parameters: [new OA\Parameter(
            name: "date",
            in: "query",
            schema: new OA\Schema(
                type: "string",
                format: "date"
            )
        )],
        responses: [new OA\Response(
            response: 200,
            description: "Meals list",
            content: new OA\JsonContent(
                title: "Meals",
                type: "array",
                items: new OA\Items(
                    type: "object",
                    ref: "#/components/schemas/Meal"
                )
            )
        )]
    )]
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
        Gate::authorize('view', $meal);

        if ($request->wantsJson()) {
            return response()->json($meal);
        }

        return view('meal.show', compact('meal'));
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
