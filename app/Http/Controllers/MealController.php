<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealStoreRequest;
use App\Http\Requests\MealUpdateRequest;
use App\Meal;
use App\Repositories\MealsRepository;
use App\Repositories\OrdersRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @param \App\Repositories\OrdersRepository $orders
     * @param \App\Repositories\MealsRepository $meals
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, OrdersRepository $orders, MealsRepository $meals)
    {
        $requestedDate = Carbon::parse($request->query('date', today()->addWeekday()));

        $todayMeals = $meals->forDate($requestedDate)->sortByPreferences();

        if ($request->wantsJson()) {
            return response()->json($todayMeals);
        }

        //$orders = $orders->mapToGroups(function ($orderItem) {
        //    return [$orderItem->order->date->toDateString() => $orderItem->meal->title.' ('.$orderItem->quantity.')'];
        //});

        $todayOrders = $orders->userOrdersForDate($requestedDate, $request->user());

        return view('meal.index', compact('todayMeals', '', 'todayOrders', 'requestedDate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', Meal::class);

        return view('meal.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\MealStoreRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(MealStoreRequest $request)
    {
        $meal = Meal::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($meal, Response::HTTP_CREATED);
        }

        if ($request->has('saveAndNew')) {
            return redirect()->route('meals.create');
        }

        return redirect()->route('meals.index');
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Meal $meal)
    {
        $this->authorize('update', $meal);

        return view('meal.edit', compact('meal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\MealUpdateRequest $request
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     */
    public function update(MealUpdateRequest $request, Meal $meal)
    {
        $meal->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json($meal);
        }

        return redirect()->route('meals.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
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

        return redirect()->route('meals.index');
    }
}
