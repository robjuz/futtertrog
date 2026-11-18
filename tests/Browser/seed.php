<?php

use App\Models\{Deposit, Meal, Order, OrderItem, User};
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (!$app->environment('testing') || $database !== storage_path('panel-browser.sqlite') || config('mail.default') !== 'array') {
    throw new RuntimeException('Browser fixtures require the isolated worktree database and array mailer.');
}
touch($database);
Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]);
User::factory()->admin()->enabled()->create(['name' => 'Panel Admin', 'email' => 'panel-admin@example.test', 'password' => Hash::make('browser-secret-123')]);
$user = User::factory()->enabled()->create(['name' => 'Panel Empfänger', 'email' => 'panel-user@example.test', 'password' => Hash::make('browser-secret-123')]);
User::factory()->admin()->enabled()->create(['name' => 'Gesperrter Admin', 'email' => 'panel-disabled@example.test', 'deleted_at' => now()]);
$meal = Meal::factory()->inFuture()->create(['title' => 'Browsergericht', 'price' => 1234]);
$order = Order::factory()->create(['status' => Order::STATUS_OPEN]);
OrderItem::factory()->create(['meal_id' => $meal->id, 'user_id' => $user->id, 'order_id' => $order->id, 'quantity' => 2]);
Deposit::factory()->create(['user_id' => $user->id, 'value' => 2000, 'comment' => 'Browser-Startguthaben']);
