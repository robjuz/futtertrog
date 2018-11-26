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

        return view('home', compact('meals'));
    }
}
