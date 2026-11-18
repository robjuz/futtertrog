<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

use App\Http\Resources\MealOptionResource;
use Inertia\Inertia;
use App\Models\Meal;
use App\Models\User;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class MealController extends Controller
{
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
    public function index(Request $request, OrdersRepository $orders, MealsRepository $meals): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Inertia\Response
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate(['date' => 'sometimes|date_format:Y-m-d', 'provider' => 'nullable|string|max:100']);

        if ($this->shouldRedirectToOtherDate($request)) {
            /** @var Meal $meal */
            $meal = Meal::where('date', '>', today())->orderBy('date')->first();

            return redirect()->route('meals.index', ['date' => $meal->date->toDateString(), 'provider' => $request->query('provider')]);
        }

        $requestedDate = Carbon::parse($request->query('date', today()));
        $todayMeals = Meal::forDate($requestedDate)
            ->whereNull('parent_id')
            ->when($request->provider === 'Internal', fn ($query) => $query->where(fn ($query) => $query->whereNull('provider')->orWhere('provider', 'Internal')), fn ($query) => $query->byProvider($request->provider))
            ->with(['parent', 'variants.parent', 'orderItems' => fn ($query) => $query->where('user_id', $user->id), 'orderItems.order', 'variants.orderItems' => fn ($query) => $query->where('user_id', $user->id), 'variants.orderItems.order'])
            ->get()
            ->sortByPreferences();

        $cards = $todayMeals->map(fn (Meal $meal): array => (new MealOptionResource($meal))->resolve($request))->values();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json($cards);
        }

        if (!config('user-interface.blade')) {
            $week = $requestedDate->copy()->startOfWeek();
            $orderedDates = $user->orderItems()->positive()->with('meal')
                ->whereHas('meal', fn ($query) => $query->whereBetween('date', [$week, $week->copy()->endOfWeek()]))
                ->get()->map(fn ($item): string => $item->meal->date->toDateString());
            $disabledDates = $user->disabledNotifications()->whereBetween('date', [$week, $week->copy()->endOfWeek()])->get()->map(fn ($notification): string => $notification->date->toDateString());

            return Inertia::render('Meals/Index', [
                'meals' => $cards,
                'date' => $requestedDate->toDateString(),
                'provider' => $request->query('provider', ''),
                'providers' => collect(['Internal' => 'Internal', ...app('mealProviders')])->map(fn (string $name, string $key): array => ['id' => $key, 'name' => $name])->values(),
                'days' => collect(range(0, 6))->map(function (int $day) use ($week, $orderedDates, $disabledDates): array {
                    $date = $week->copy()->addDays($day);
                    return ['date' => $date->toDateString(), 'label' => $date->translatedFormat('D d.m.'),
                        'ordered' => $orderedDates->contains($date->toDateString()),
                        'notificationDisabled' => $disabledDates->contains($date->toDateString())];
                }),
                'previousWeek' => $week->copy()->subWeek()->toDateString(),
                'nextWeek' => Meal::where('date', '>=', $week->copy()->addWeek())->exists() ? $week->copy()->addWeek()->toDateString() : null,
                'notificationDisabled' => $user->disabledNotifications()->whereDate('date', $requestedDate)->exists(),
                'notificationUrl' => route('notification.disable', absolute: false),
            ]);
        }

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
    public function show(Request $request, Meal $meal): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Inertia\Response
    {
        Gate::authorize('view', $meal);

        $displayMeal = $meal->parent ?? $meal;
        $displayMeal->load(['parent', 'variants.parent', 'orderItems.order', 'variants.orderItems.order']);
        $card = (new MealOptionResource($displayMeal))->resolve($request);
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json($card);
        }
        if (!config('user-interface.blade')) {
            return Inertia::render('Meals/Show', ['meal' => $card]);
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
