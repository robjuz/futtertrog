<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        /** @var User $user */
        $user = $request->user();

        $balance = $user->balance;
        $todayMeals = $user->meals()->whereDate('date', today())->get();

        $mealsHistory = $user->meals()->orderBy('meal_user.created_at', 'desc')->paginate(5, ['*'], 'meals_page');
        $mealsHistory->appends('deposits_page', $request->deposits_page);
        $mealsHistory->appends('future_meals_page', $request->future_meals_page);

        $futureMeals = $user->meals()->whereDate('date', '>', today())->orderBy('date')->paginate(5, ['*'], 'future_meals_page');
        $futureMeals->appends('meals_page', $request->meals_page);
        $futureMeals->appends('deposits_page', $request->deposits_page);

        $deposits = $user->deposits()->latest()->paginate(5, ['*'], 'deposits_page');
        $deposits->appends('meals_page', $request->meals_page);
        $deposits->appends('future_meals_page', $request->future_meals_page);

        return view('home', compact('balance','mealsHistory', 'todayMeals','futureMeals', 'deposits', 'user'));
    }
}
