<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\MealController;
use App\Http\Controllers\OrderItemController;

Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');
//Auth::routes(['register' => false]);

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('order_possibilities', [MealController::class, 'index']);
    Route::get('orders', [OrderItemController::class, 'index']);

    Route::post('place_order', [OrderItemController::class, 'store']);

});
