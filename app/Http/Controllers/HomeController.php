<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {

        $meals = auth()->user()->meals()->whereDate('date', today())->get();

        $futureMeals = auth()->user()->meals()->whereDate('date', '>', today())->limit(5)->get();

        $count = auth()->user()->meals()->whereDate('date', '>', today())->count();

        return view('home', compact('meals', 'futureMeals', 'count'));
    }
}
