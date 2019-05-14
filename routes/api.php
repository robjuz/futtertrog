<?php

use Illuminate\Http\Request;

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

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::post('/login', 'Auth\Api\LoginController@login')->name('api.login');
Route::post('/logout', 'Auth\Api\LoginController@logout')->name('api.logout');
Route::post('/password/email', 'Auth\Api\ForgotPasswordController@sendResetLinkEmail')->name('api.password.email');
Route::post('/password/reset', 'Auth\Api\ResetPasswordController@reset')->name('api.password.reset');



/*
|--------------------------------------------------------------------------
| Laravel Passport Routes
|--------------------------------------------------------------------------
|
| If you'd like to use full functionality of laravel passport
| use the routes below instead of registering your routes in
| AuthServiceProvider as instructed in passport documentation.
| This will use "auth:api" guard instead of "auth"
|
*/

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('user', function() {
        return auth()->user();
    });

    Route::get('meals/ical', 'IcalController')->name('meals.ical');
    Route::apiResource('meals', 'MealController');
    Route::apiResource('orders', 'OrderController')->only(['index', 'update', 'destroy']);
    Route::apiResource('order_items', 'OrderItemController')->only(['index', 'create', 'store', 'destroy']);
    Route::apiResource('users', 'UserController');

    Route::apiResource('deposits', 'DepositsController')->only(['store', 'destroy']);
    Route::apiResource('settings', 'SettingsController')->only(['index', 'store']);

    Route::post('/subscriptions', 'PushSubscriptionController@update');
});

Route::delete('/subscriptions', 'PushSubscriptionController@destroy');