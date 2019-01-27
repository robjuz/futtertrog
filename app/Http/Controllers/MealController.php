<?php

namespace App\Http\Controllers;

use App\Meal;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('list', Meal::class);

        $requestedDate = Carbon::parse($request->query('date', today()->addWeekday()));
        $firstOfMonth = Carbon::parse($requestedDate)->firstOfMonth();

        $settings = $request->user()->settings ?? [];

        $includes = $request->has('reset') ? $settings['includes'] ?? null : $request->query('includes');
        $excludes = $request->has('reset') ? $settings['excludes'] ?? null : $request->query('excludes');

        $todayMeals = Meal::query()
            ->whereDate('date_from', '<=', $requestedDate)
            ->whereDate('date_to', '>=', $requestedDate)
//            ->when(!empty($includes), function (Builder $query) use ($includes) {
//                $includes = array_map('trim', explode(',', $includes));
//
//                $query->where(function (Builder $query) use ($includes) {
//                    foreach ($includes as $include) {
//                        $query->orWhere('description', 'like', '%' . $include . '%');
//                    }
//                });
//            })
//            ->when(!empty($excludes), function (Builder $query) use ($excludes) {
//                $excludes = array_map('trim', explode(',', $excludes));
//
//                foreach ($excludes as $exclude) {
//                    $query->where('description', 'not like', '%' . $exclude . '%');
//                }
//            })
            ->get();

        if ($request->wantsJson()) {
            return $todayMeals;
        }

        $meals = collect();

        //TODO: optimize
        for ($day = 0; $day <= $firstOfMonth->daysInMonth; $day++) {
            $meals[$firstOfMonth->toDateString()] = Meal::whereDate('date_from', '>=', $firstOfMonth)->whereDate('date_to', '<=', $firstOfMonth)->exists();
            $firstOfMonth->addDay();
        }

        /** @var User $user */
        $user = $request->user();
        $orders = $user->orders()
            ->with('meal')
            ->whereYear('date', $requestedDate->year)
            ->whereMonth('date', $requestedDate->month)
            ->get()
            ->mapToGroups(function ($order, $key) {
                return [$order->date->toDateString() => $order->meal->title . ' (' . $order->quantity . ')'];
            });



        return view('meal.index', compact('meals', 'todayMeals', 'orders', 'requestedDate', 'includes', 'excludes'));
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
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Meal::class);

        Meal::create($request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'provider' => ['required', Rule::in(Meal::$providers)]
        ]));

        if ($request->has('saveAndNew')) {
            return redirect()->route('meals.create');
        }

        return redirect()->route('meals.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Meal $meal)
    {
        $this->authorize('view', $meal);

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
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Meal                $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Meal $meal)
    {
        $this->authorize('update', $meal);

        $meal->update(
            $request->validate([
                'date' => 'sometimes|date',
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'can_be_ordered_until' => 'sometimes|date|after:date',
                'provider' => ['sometimes', Rule::in(Meal::$providers)]
            ])
        );

        return redirect()->route('meals.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request    $request
     * @param  \App\Meal $meal
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Request $request, Meal $meal)
    {
        $this->authorize('delete', $meal);

        if ($meal->users()->count()) {

            if ($request->wantsJson()) {
                abort(Response::HTTP_FORBIDDEN,
                    'Dieses Menu wurde bereits bestellt! Bitte erst alle Bestellungen löschen.');
            }

            return back()->withErrors([
                'Dieses Menu wurde bereits bestellt! Bitte erst alle Bestellungen löschen.'
            ]);
        }

        $meal->delete();

        if ($request->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }

        return redirect()->route('meals.index');
    }
}
