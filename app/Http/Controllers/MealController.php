<?php

namespace App\Http\Controllers;

use App\Meal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $meals = Meal::orderBy('date')
            ->whereDate('date', $request->query('date', today()))
            ->get();

        if ($request->wantsJson()) {
            return $meals;
        }

        $orders = $request->user()->meals;

        return view('meal.index', compact('meals', 'orders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('meal.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Meal::create($request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0'
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
     * @return \Illuminate\Http\Response
     */
    public function show(Meal $meal)
    {
        return view('meal.show', compact('meal'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Meal $meal
     * @return \Illuminate\Http\Response
     */
    public function edit(Meal $meal)
    {
        return view('meal.edit', compact('meal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Meal $meal
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meal $meal)
    {
        $meal->update(
            $request->validate([
                'date' => 'required|date',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0'
            ])
        );

        return redirect()->route('meals.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param  \App\Meal $meal
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Request $request, Meal $meal)
    {

        if ($meal->users()->count()) {

            if ($request->wantsJson()) {
                abort(Response::HTTP_FORBIDDEN, 'Dieses Menu wurde bereits bestellt! Bitte erst alle Bestellungen löschen.');
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
