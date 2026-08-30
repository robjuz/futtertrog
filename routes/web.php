<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AutoOrderController;
use App\Http\Controllers\DepositsController;
use App\Http\Controllers\DepositTransferController;
use App\Http\Controllers\DisabledNotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MealImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
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

Route::get('login/oauth/{provider}', [LoginController::class, 'redirectToOauth'])->name('login.oauth');
Route::get('login/oauth/{provider}/callback', [LoginController::class, 'handleOauthCallback'])->name('login.oauth-callback');

Route::group(['middleware' => 'auth:web,api'], function () {
    Route::get('meals/ical', IcalController::class)->name('meals.ical');
});

Route::group(['middleware' => 'auth:web'], function () {
    Route::get('/', HomeController::class)->name('home');

    Route::post('meals/import', MealImportController::class)->name('meals.import');
    Route::resource('meals', MealController::class);

    Route::post('orders/{order}/auto_order', AutoOrderController::class)->name('orders.auto_order');
    Route::resource('orders', OrderController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::resource('order_items', OrderItemController::class)->except(['show']);
    Route::post('order_items/json', [OrderItemController::class, 'store_json']);

    Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::resource('users', UserController::class);

    Route::get('deposits/transfer', [DepositTransferController::class, 'create']);
    Route::post('deposits/transfer', [DepositTransferController::class, 'store'])->name('deposits.transfer');
    Route::resource('deposits', DepositsController::class)->except('show');
    Route::resource('settings', SettingsController::class)->only(['index', 'store']);

    Route::get('notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::post('notifications', [NotificationController::class, 'store'])->name('notification.store');

    Route::post('notifications/disable', [DisabledNotificationController::class, 'store'])->name('notification.disable');
    Route::delete('notifications/disable', [DisabledNotificationController::class, 'destroy']);

});
