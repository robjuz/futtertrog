<?php

use App\Http\Controllers\AdminOrderItemController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AutoOrderController;
use App\Http\Controllers\DepositsController;
use App\Http\Controllers\DepositTransferController;
use App\Http\Controllers\DisabledNotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MealImportController;
use App\Http\Controllers\MyBalanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\RejectAdministrativeInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['register' => false]);
Route::get('login/oauth/{provider}', [LoginController::class, 'redirectToOauth'])->name('login.oauth');
Route::get('login/oauth/{provider}/callback', [LoginController::class, 'handleOauthCallback'])->name('login.oauth-callback');

Route::middleware(['throttle:10,1', RejectAdministrativeInput::class])->group(function (): void {
    Route::get('credentials/{user}/{purpose}/{token}', [\App\Http\Controllers\Auth\CredentialChallengeController::class, 'show'])
        ->whereNumber('user')->name('credentials.show');
    Route::post('credentials/{user}/{purpose}/{token}', [\App\Http\Controllers\Auth\CredentialChallengeController::class, 'confirm'])
        ->whereNumber('user')->name('credentials.confirm');
});

Route::get('meals/ical', IcalController::class)
    ->middleware(['auth:web,api', EnsureActiveAccount::class, RejectAdministrativeInput::class])->name('meals.ical');

Route::middleware(['auth:web', EnsureActiveAccount::class])->group(function (): void {
    Route::middleware(RejectAdministrativeInput::class)->group(function (): void {
        Route::get('/', HomeController::class)->name('home');
        Route::resource('meals', MealController::class)->only(['index', 'show'])->whereNumber('meal');
        Route::get('my/orders', [OrderItemController::class, 'index'])->name('my.orders');
        Route::get('my/balance', MyBalanceController::class)->name('my.balance');
        Route::resource('order_items', OrderItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::prefix('profile')->middleware(\App\Http\Middleware\HandleInertiaRequests::class)->group(function (): void {
            Route::get('/', [\App\Http\Controllers\ProfileController::class, 'account'])->name('profile.account');
            Route::patch('/', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
            Route::get('security', [\App\Http\Controllers\ProfileController::class, 'security'])->name('profile.security');
            Route::put('password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
            Route::post('password/confirmation', [\App\Http\Controllers\Auth\CredentialChallengeController::class, 'send'])
                ->middleware('throttle:10,1')->name('profile.password.confirmation');
            Route::get('preferences', [\App\Http\Controllers\ProfileController::class, 'preferences'])->name('profile.preferences');
            Route::patch('preferences', [\App\Http\Controllers\ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
        });
        Route::resource('settings', SettingsController::class)->only(['index', 'store']);
        Route::post('notifications/disable', [DisabledNotificationController::class, 'store'])->name('notification.disable');
        Route::delete('notifications/disable', [DisabledNotificationController::class, 'destroy']);
    });

    Route::prefix('admin')->middleware('can:access-administration')->group(function (): void {
        Route::view('/', 'admin.index')->name('admin.index');
        Route::get('meals', [MealController::class, 'adminIndex'])->name('admin.meals.index');
        Route::post('meals/import', MealImportController::class)->name('meals.import');
        Route::resource('meals', MealController::class)->except(['index', 'show']);
        Route::post('orders/{order}/auto_order', AutoOrderController::class)->name('orders.auto_order');
        Route::resource('orders', OrderController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::resource('order_items', AdminOrderItemController::class)->except('show')->names('admin.order_items');
        Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::resource('users', UserController::class);
        Route::get('deposits/transfer', [DepositTransferController::class, 'create'])->name('deposits.transfer.create');
        Route::post('deposits/transfer', [DepositTransferController::class, 'store'])->name('deposits.transfer');
        Route::resource('deposits', DepositsController::class)->except('show');
        Route::get('notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('notifications', [NotificationController::class, 'store'])->name('notification.store');
    });

    // Old bookmarks pass the same administrative boundary before redirecting.
    Route::middleware('can:access-administration')->group(function (): void {
        foreach (['users', 'users/create', 'users/{user}', 'users/{user}/edit',
            'meals/create', 'meals/{meal}/edit', 'orders', 'orders/{order}/edit',
            'order_items/create', 'order_items/{order_item}/edit',
            'deposits', 'deposits/create', 'deposits/{deposit}/edit', 'deposits/transfer',
            'notifications/create'] as $path) {
            Route::get($path, function (Request $request) {
                return redirect()->to('/admin/'.$request->path().($request->getQueryString() ? '?'.$request->getQueryString() : ''));
            });
        }
    });
});
