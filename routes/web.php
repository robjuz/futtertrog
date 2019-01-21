<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');
Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', 'HomeController');

    Route::resource('meals' ,'MealController');
    Route::resource('orders', 'OrderController');
    Route::resource('users' ,'UserController');

    Route::resource('deposits', 'DepositController')->only(['store', 'destroy']);
    Route::resource('settings', 'SettingsController')->only(['index', 'store']);

    Route::post('user_meals}', 'UserOrderController@store')->name('user_meals.store');
    Route::delete('user_meals/{meal}', 'UserOrderController@destroy')->name('user_meals.destroy');
    Route::get('user_meals', 'UserOrderController@index')->name('user_meals.index');
    Route::get('user_meals/create', 'UserOrderController@create')->name('user_meals.create');

});