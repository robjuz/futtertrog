<?php

namespace Tests\Feature;

use App\Actions\Admin\Bookings;
use App\Models\AdminActivity;
use App\Models\Deposit;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminActivityMigrationTest extends TestCase
{
    #[Test]
    public function the_additive_audit_migration_preserves_existing_accounts_orders_and_bookings(): void
    {
        config(['database.connections.admin_migration_fixture' => array_merge(config('database.connections.sqlite'), ['database' => ':memory:'])]);
        $original = config('database.default');
        $migration = 'database/migrations/2026_11_18_103927_create_admin_activities_table.php';
        $baseline = array_values(array_filter(array_map(
            fn (string $path): string => str_replace(base_path().'/', '', $path), glob(database_path('migrations/*.php')),
        ), fn (string $path): bool => $path !== $migration));
        try {
            $this->artisan('migrate:fresh', ['--database' => 'admin_migration_fixture', '--path' => $baseline,
                '--force' => true, '--no-interaction' => true])->assertExitCode(0);
            config(['database.default' => 'admin_migration_fixture']);
            $actor = User::factory()->admin()->create();
            $item = OrderItem::factory()->create();
            Deposit::factory()->create(['user_id' => $item->user_id, 'value' => 2500]);
            $before = [];
            foreach (['users', 'meals', 'orders', 'order_items', 'deposits'] as $table) {
                $before[$table] = DB::table($table)->orderBy('id')->get()->all();
            }

            $this->artisan('migrate', ['--database' => 'admin_migration_fixture', '--path' => $migration,
                '--force' => true, '--no-interaction' => true])->assertExitCode(0);

            foreach ($before as $table => $rows) {
                $this->assertEquals($rows, DB::table($table)->orderBy('id')->get()->all(), $table);
            }
            app(Bookings::class)->create($actor, ['user_id' => $item->user_id, 'value' => 100], 'after-upgrade');
            $this->assertSame($actor->id, AdminActivity::sole()->actor->id);
            $this->artisan('migrate:rollback', ['--database' => 'admin_migration_fixture', '--path' => $migration,
                '--force' => true, '--no-interaction' => true])->assertExitCode(0);
            $this->assertFalse(Schema::hasTable('admin_activities'));
            $this->assertSame(2, Deposit::count());
            $this->assertSame(1, OrderItem::count());
        } finally {
            config(['database.default' => $original]);
            DB::purge('admin_migration_fixture');
        }
    }

    #[Test]
    public function the_activity_factory_links_the_actor_and_target_without_model_serialization(): void
    {
        $activity = AdminActivity::factory()->create();

        $this->assertTrue($activity->actor->is_admin);
        $this->assertModelExists(Deposit::findOrFail($activity->target_id));
        $this->assertSame(['before' => [], 'after' => ['value' => 100]], $activity->changes);
        $this->assertStringNotContainsString('password', $activity->toJson());
    }
}
