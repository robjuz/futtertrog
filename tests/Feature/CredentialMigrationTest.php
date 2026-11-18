<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Meal;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CredentialMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.credential_fixture' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
    }

    protected function tearDown(): void
    {
        DB::purge('credential_fixture');
        config(['database.default' => 'sqlite']);
        parent::tearDown();
    }

    #[Test]
    public function fresh_installation_creates_credentials_and_keeps_all_business_tables(): void
    {
        $this->artisan('migrate:fresh', ['--database' => 'credential_fixture', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $schema = Schema::connection('credential_fixture');
        foreach (['users', 'meals', 'orders', 'order_items', 'deposits', 'external_identities', 'credential_challenges'] as $table) {
            $this->assertTrue($schema->hasTable($table), $table);
        }
        $this->assertTrue($schema->hasColumn('users', 'local_credential_status'));
    }

    #[Test]
    public function additive_upgrade_marks_every_existing_account_legacy_and_preserves_business_data(): void
    {
        $baseline = array_map(fn (string $path): string => str_replace(base_path().'/', '', $path), glob(database_path('migrations/0001_*.php')));
        $this->artisan('migrate:fresh', [
            '--database' => 'credential_fixture', '--path' => $baseline, '--force' => true, '--no-interaction' => true,
        ])->assertExitCode(0);
        config(['database.default' => 'credential_fixture']);
        $hash = Hash::make('unchanged-legacy-password');
        $rows = [];
        foreach ([['Local', false, null], ['Externally used', false, null], ['Admin', true, null], ['Inactive', false, now()->toDateTimeString()]] as $i => [$name, $admin, $deleted]) {
            $rows[] = [
                'id' => $i + 1, 'name' => $name, 'email' => "legacy-$i@example.com",
                'password' => $i === 1 ? Hash::make('formerly-known-provider-id') : $hash,
                'is_admin' => $admin, 'api_token' => "calendar-$i", 'deleted_at' => $deleted,
                'settings' => json_encode(['language' => 'en', 'mealPreferences' => 'rice', 'noOrderNotification' => true]),
            ];
        }
        DB::table('users')->insert($rows);
        $meal = Meal::factory()->create(['price' => 875]);
        $order = Order::factory()->create(['user_id' => 1, 'status' => 'closed']);
        OrderItem::factory()->create(['user_id' => 1, 'meal_id' => $meal->id, 'order_id' => $order->id, 'quantity' => 2]);
        Deposit::factory()->create(['user_id' => 1, 'value' => 2500]);
        $before = [];
        foreach (['users', 'meals', 'orders', 'order_items', 'deposits'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all();
        }
        $this->assertFalse(Schema::hasColumn('users', 'local_credential_status'));
        $this->artisan('migrate', ['--database' => 'credential_fixture', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame(['legacy', 'legacy', 'legacy', 'legacy'], DB::table('users')->orderBy('id')->pluck('local_credential_status')->all());
        foreach ($before as $table => $expected) {
            $actual = DB::table($table)->orderBy('id')->get()->map(function ($row): array {
                $row = (array) $row;
                unset($row['local_credential_status']);
                return $row;
            })->all();
            $this->assertSame($expected, $actual, $table);
        }
    }
}
