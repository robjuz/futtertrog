<?php

use App\Http\Controllers\DisabledNotificationController;
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

Route::group(['middleware' => 'enableLoginWithGitlab'], function () {
    Route::get('login/gitlab', 'Auth\LoginController@redirectToGitlab')->name('login.gitlab');
    Route::get('login/gitlab/callback', 'Auth\LoginController@handleGitlabCallback')->name('login.gitlab-callback');
});

Route::view('/', 'landing-page')->middleware('guest');
Route::view('/pot-generator', 'tools/pot-generator');
Route::post('/pot-generator', 'PotGeneratorController');

Route::group(['middleware' => 'auth:web,api'], function () {
    Route::get('meals/ical', 'IcalController')->name('meals.ical');
});

Route::group(['middleware' => 'auth:web'], function () {
    Route::get('/dashboard', 'HomeController')->name('home');

    Route::post('meals/import', 'MealImportController')->name('meals.import');
    Route::resource('meals', 'MealController');

    Route::post('orders/{order}/auto_order', 'AutoOrderController')->name('orders.auto_order');
    Route::resource('orders', 'OrderController')->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('order_items', 'OrderItemController')->except(['show']);
    Route::post('order_items/json', 'OrderItemController@store_json');


    Route::post('users/{user}/restore', 'UserController@restore')->name('users.restore');
    Route::resource('users', 'UserController');

    Route::get('deposits/transfer', 'DepositTransferController@create');
    Route::post('deposits/transfer', 'DepositTransferController@store')->name('deposits.transfer');
    Route::resource('deposits', 'DepositsController')->except('show');
    Route::resource('settings', 'SettingsController')->only(['index', 'store']);

    Route::get('notifications/create', 'NotificationController@create')->name('notifications.create');
    Route::post('notifications', 'NotificationController@store')->name('notification.store');

    Route::post('notifications/disable', [DisabledNotificationController::class, 'store'])->name('notification.disable');
    Route::delete('notifications/disable', [DisabledNotificationController::class, 'destroy']);

    Route::post('/subscriptions', 'PushSubscriptionController@update');
});

Route::delete('/subscriptions', 'PushSubscriptionController@destroy');
