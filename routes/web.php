<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DisabledNotificationController;
use App\Http\Controllers\IcalController;
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

Route::get('logout', [LoginController::class, 'logout']);
Auth::routes(['register' => false]);

Route::group(['middleware' => 'enableLoginWithGitlab'], function () {
    Route::get('login/gitlab', [LoginController::class, 'redirectToGitlab'])->name('login.gitlab');
    Route::get('login/gitlab/callback', [LoginController::class, 'handleGitlabCallback'])->name('login.gitlab-callback');
});

Route::group(['middleware' => 'auth:web,api'], function () {
    Route::get('meals/ical', IcalController::class)->name('meals.ical');
});

Route::group(['middleware' => 'auth:web'], function () {
    Route::get('/', \App\Http\Controllers\HomeController::class)->name('home');

    Route::post('meals/import', \App\Http\Controllers\MealImportController::class)->name('meals.import');
    Route::resource('meals', \App\Http\Controllers\MealController::class);

    Route::post('orders/{order}/auto_order', \App\Http\Controllers\AutoOrderController::class)->name('orders.auto_order');
    Route::resource('orders', \App\Http\Controllers\OrderController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('order_items', \App\Http\Controllers\OrderItemController::class)->except(['show']);
    Route::post('order_items/json', [\App\Http\Controllers\OrderItemController::class, 'store_json']);


    Route::post('users/{user}/restore', [\App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');
    Route::resource('users', \App\Http\Controllers\UserController::class);

    Route::get('deposits/transfer', [\App\Http\Controllers\DepositTransferController::class, 'create']);
    Route::post('deposits/transfer', [\App\Http\Controllers\DepositTransferController::class, 'store'])->name('deposits.transfer');
    Route::resource('deposits', \App\Http\Controllers\DepositsController::class)->except('show');
    Route::resource('settings', \App\Http\Controllers\SettingsController::class)->only(['index', 'store']);

    Route::get('notifications/create', [\App\Http\Controllers\NotificationController::class, 'create'])->name('notifications.create');
    Route::post('notifications', [\App\Http\Controllers\NotificationController::class, 'store'])->name('notification.store');

    Route::post('notifications/disable', [DisabledNotificationController::class, 'store'])->name('notification.disable');
    Route::delete('notifications/disable', [DisabledNotificationController::class, 'destroy']);

});

