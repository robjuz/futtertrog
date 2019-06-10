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
Auth::routes(['register' => false]);

Route::group(['middleware' => 'auth:web,api'], function () {
    Route::get('/', 'HomeController')->name('home');

    Route::get('meals/ical', 'IcalController')->name('meals.ical');
    Route::resource('meals', 'MealController');
    Route::resource('orders', 'OrderController')->only(['index', 'update', 'destroy']);
    Route::resource('order_items', 'OrderItemController')->only(['index', 'create', 'store', 'destroy']);
    Route::resource('users', 'UserController');

    Route::get('deposites/transfer', 'DepositTransferController@show');
    Route::post('deposites/transfer', 'DepositTransferController@store')->name('deposites.transfer');
    Route::resource('deposits', 'DepositsController')->only(['store', 'destroy']);
    Route::resource('settings', 'SettingsController')->only(['index', 'store']);

    Route::post('/subscriptions', 'PushSubscriptionController@update');
});

Route::delete('/subscriptions', 'PushSubscriptionController@destroy');
