<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DisabledNotificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IcalController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MyBalanceController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\SettingsController;
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
        Route::get('overview', fn () => redirect()->route('filament.administration.pages.dashboard'))->name('admin.index');
        Route::get('panel', fn () => redirect()->route('filament.administration.pages.dashboard'));

        $links = [
            ['users', 'users.index', \App\Filament\Resources\Users\UserResource::class, null, null, 'viewAny'],
            ['users/create', 'users.create', \App\Filament\Resources\Users\UserResource::class, 'create', null, 'create'],
            ['users/{user}/edit', 'users.edit', \App\Filament\Resources\Users\UserResource::class, 'edit', \App\Models\User::class, 'update'],
            ['meals/create', 'meals.create', \App\Filament\Resources\Meals\MealResource::class, 'add', null, 'create'],
            ['meals', 'admin.meals.index', \App\Filament\Resources\Meals\MealResource::class, null, null, 'create'],
            ['meals/{meal}/edit', 'meals.edit', \App\Filament\Resources\Meals\MealResource::class, 'change', \App\Models\Meal::class, 'update'],
            ['orders', 'orders.index', \App\Filament\Resources\Orders\OrderResource::class, null, null, 'list'],
            ['orders/{order}/edit', 'orders.edit', \App\Filament\Resources\Orders\OrderResource::class, 'change', \App\Models\Order::class, 'update'],
            ['order_items', 'admin.order_items.index', \App\Filament\Resources\OrderItems\OrderItemResource::class, null, null, 'administerAny'],
            ['order_items/create', 'admin.order_items.create', \App\Filament\Resources\OrderItems\OrderItemResource::class, 'orderFor', null, 'administerAny'],
            ['order_items/{order_item}/edit', 'admin.order_items.edit', \App\Filament\Resources\OrderItems\OrderItemResource::class, 'change', \App\Models\OrderItem::class, 'administer'],
            ['deposits', 'deposits.index', \App\Filament\Resources\Deposits\DepositResource::class, null, null, 'viewAny'],
            ['deposits/create', 'deposits.create', \App\Filament\Resources\Deposits\DepositResource::class, 'book', null, 'create'],
            ['deposits/transfer', 'deposits.transfer.create', \App\Filament\Resources\Deposits\DepositResource::class, 'transfer', null, 'create'],
            ['deposits/{deposit}/edit', 'deposits.edit', \App\Filament\Resources\Deposits\DepositResource::class, 'change', \App\Models\Deposit::class, 'update'],
        ];
        foreach ($links as [$path, $name, $resource, $action, $model, $ability]) {
            Route::get($path, function (Request $request) use ($resource, $action, $model, $ability) {
                $record = $model ? $model::findOrFail(array_values($request->route()->parameters())[0]) : null;
                \Illuminate\Support\Facades\Gate::authorize($ability, $record ?? $resource::getModel());
                $parameters = [];
                if ($action) {
                    $parameters = $record ? ['tableAction' => $action, 'tableActionRecord' => $record->id] : ['action' => $action];
                }
                foreach (['provider' => 'provider', 'status' => 'status', 'user_id' => $resource === \App\Filament\Resources\Orders\OrderResource::class ? 'recipient' : 'user_id'] as $old => $filter) {
                    if ($request->filled($old)) {
                        $parameters['filters'][$filter]['value'] = $request->query($old);
                    }
                }
                if ($request->filled('from') || $request->filled('to')) {
                    $parameters['filters']['dates'] = ['from' => $request->query('from'), 'until' => $request->query('to')];
                }
                if ($request->filled('payed')) {
                    $parameters['filters']['payed_at']['value'] = $request->boolean('payed') ? '1' : '0';
                }

                return redirect()->to($resource::getUrl('index', $parameters));
            })->name($name);
        }
        Route::get('users/{user}', function (string $user) {
            $record = \App\Models\User::findOrFail($user);
            \Illuminate\Support\Facades\Gate::authorize('view', $record);

            return redirect()->to(\App\Filament\Resources\Users\UserResource::getUrl('view', ['record' => $record]));
        })->name('users.show');
        Route::get('notifications/create', function () {
            \Illuminate\Support\Facades\Gate::authorize('send-notifications');

            return redirect()->to(\App\Filament\Pages\Notifications::getUrl(['action' => 'send']));
        })->name('notifications.create');

        // Explicit tombstones keep named callers resolvable without retaining write handlers.
        foreach ([
            ['POST', 'users', 'users.store'], ['PUT|PATCH', 'users/{user}', 'users.update'],
            ['DELETE', 'users/{user}', 'users.destroy'], ['POST', 'users/{user}/restore', 'users.restore'],
            ['POST', 'meals', 'meals.store'],
            ['POST', 'meals/import', 'meals.import'],
            ['PUT|PATCH', 'meals/{meal}', 'meals.update'], ['DELETE', 'meals/{meal}', 'meals.destroy'],
            ['POST', 'orders/{order}/auto_order', 'orders.auto_order'],
            ['PUT|PATCH', 'orders/{order}', 'orders.update'], ['DELETE', 'orders/{order}', 'orders.destroy'],
            ['POST', 'order_items', 'admin.order_items.store'], ['PUT|PATCH', 'order_items/{order_item}', 'admin.order_items.update'],
            ['DELETE', 'order_items/{order_item}', 'admin.order_items.destroy'],
            ['POST', 'deposits', 'deposits.store'], ['POST', 'deposits/transfer', 'deposits.transfer'],
            ['PUT|PATCH', 'deposits/{deposit}', 'deposits.update'], ['DELETE', 'deposits/{deposit}', 'deposits.destroy'],
            ['POST', 'notifications', 'notification.store'],
        ] as [$methods, $path, $name]) {
            Route::match(explode('|', $methods), $path, fn () => abort(410, 'Dieser Schreibweg wurde durch das Verwaltungs-Panel ersetzt.'))->name($name);
        }
    });

    // Old bookmarks cross the administrative boundary before reaching their protected target.
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
