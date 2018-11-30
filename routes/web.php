<?php

use \Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController');


Route::resources([
    'meals' => 'MealController',
    'orders' => 'OrderController',
    'users' => 'UserController'
]);


Route::post('user_meal/{meal}', 'UserOrderController@toggle')->name('user_meal');
Route::get('user_meals', 'UserOrderController@index')->name('user_meals');