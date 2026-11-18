<?php

namespace Tests\Feature;

use App\Actions\Admin\Bookings;
use App\Actions\Admin\Users;
use App\Models\AdminActivity;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * MariaDB keeps both sessions through the lock timeout, commit and retry.
 * SQLite uses WAL for overlapping readers and refreshes the observer after
 * SQLITE_BUSY to discard the failed statement's retained read snapshot.
 */
class AdminDatabaseConcurrencyTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        $this->createFixtureDatabase('concurrency_fixture', ['concurrency_competitor']);
        $this->artisan('migrate:fresh', ['--database' => 'concurrency_fixture', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        DB::setDefaultConnection('concurrency_fixture');
        $primary = DB::connection();
        $competitor = DB::connection('concurrency_competitor');
        $this->assertNotSame($primary->getPdo(), $competitor->getPdo());
        $this->assertSame($primary->getDriverName(), $competitor->getDriverName());
        if ($primary->getDriverName() === 'sqlite') {
            $primary->statement('PRAGMA journal_mode = WAL');
            $primary->statement('PRAGMA busy_timeout = 1000');
            $competitor->statement('PRAGMA busy_timeout = 1000');
        } else {
            $this->assertNotSame($primary->selectOne('SELECT CONNECTION_ID() AS id')->id,
                $competitor->selectOne('SELECT CONNECTION_ID() AS id')->id);
            $competitor->statement('SET SESSION innodb_lock_wait_timeout = 1');
        }
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    #[Test]
    #[TestWith(['booking'])]
    #[TestWith(['transfer'])]
    public function overlapping_requests_with_the_same_key_book_exactly_once_across_connections(string $operation): void
    {
        $actor = User::factory()->admin()->create();
        $source = User::factory()->create();
        $target = User::factory()->create();
        $key = 'same-financial-operation';
        $data = $operation === 'booking'
            ? ['user_id' => $target->id, 'value' => 1250]
            : ['source' => $source->id, 'target' => $target->id, 'value' => 1250];
        $book = fn (): array => $operation === 'booking'
            ? [app(Bookings::class)->create($actor, $data, $key)->id]
            : array_map(fn (Deposit $deposit): int => $deposit->id, app(Bookings::class)->transfer($actor, $data, $key));
        $primary = DB::connection();
        $primary->beginTransaction();
        try {
            $ids = $book();
            $this->assertSame(1, $primary->transactionLevel());
            $this->assertSame(0, DB::connection('concurrency_competitor')->table('deposits')->count());
            $this->assertSame(0, DB::connection('concurrency_competitor')->table('admin_activities')->count());

            $this->assertCompetingWriteBlocked($book, 'id');
            $this->assertDatabaseCount('deposits', $operation === 'booking' ? 1 : 2);
            $this->assertDatabaseCount('admin_activities', 1);
            $primary->commit();

            $this->assertSame($ids, $this->onConnection('concurrency_competitor', $book));
            $this->onConnection('concurrency_competitor', function () use ($operation): void {
                $this->assertSame($operation === 'booking' ? [1250] : [-1250, 1250],
                    Deposit::orderBy('id')->pluck('value')->all());
                $this->assertDatabaseCount('admin_activities', 1);
                $this->assertSame($operation === 'booking' ? 'deposit.created' : 'deposit.transferred', AdminActivity::sole()->action);
            });
        } finally {
            $primary->rollBack(0);
        }
    }

    #[Test]
    #[TestWith(['demote', 'demote'])]
    #[TestWith(['demote', 'deactivate'])]
    #[TestWith(['deactivate', 'demote'])]
    #[TestWith(['deactivate', 'deactivate'])]
    public function overlapping_admin_removals_block_and_preserve_an_active_administrator(string $first, string $second): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $remove = function (string $operation, User $actor, User $target): void {
            if ($operation === 'demote') {
                app(Users::class)->changeRole($actor, $target, false);
            } else {
                app(Users::class)->deactivate($actor, $target);
            }
        };
        $firstTarget = $first === 'demote' ? $adminA : $adminB;
        $secondTarget = $first === 'demote' ? $adminB : $adminA;
        $secondActor = $second === 'demote' ? $secondTarget : $firstTarget;
        $competingRemoval = fn () => $remove($second, $secondActor, $secondTarget);
        $primary = DB::connection();
        $primary->beginTransaction();
        try {
            $remove($first, $adminA, $firstTarget);
            $this->assertSame(1, $primary->transactionLevel());
            $this->assertSame(2, DB::connection('concurrency_competitor')->table('users')
                ->where('is_admin', true)->whereNull('deleted_at')->count());

            $this->assertCompetingWriteBlocked($competingRemoval, 'is_admin');
            $primary->commit();

            try {
                $this->onConnection('concurrency_competitor', $competingRemoval);
                $this->fail('The remaining administrator or the now inactive actor must prevent the second removal.');
            } catch (AuthorizationException $exception) {
                $this->assertSame(403, $exception->status() ?? 403);
            }
            $this->onConnection('concurrency_competitor', function () use ($first, $adminA, $adminB): void {
                $this->assertSame($first === 'demote' ? $adminB->id : $adminA->id, User::where('is_admin', true)->sole()->id);
                $this->assertDatabaseCount('admin_activities', 1);
                $this->assertSame($first === 'demote' ? 'user.updated' : 'user.deactivated', AdminActivity::sole()->action);
            });
        } finally {
            $primary->rollBack(0);
        }
    }

    /** Hold the first action's locks open while the other real connection attempts its action. */
    private function assertCompetingWriteBlocked(\Closure $operation, string $lockedColumn): void
    {
        try {
            $this->onConnection('concurrency_competitor', $operation);
            $this->fail('An overlapping write must wait for the first transaction.');
        } catch (QueryException $exception) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $this->assertSame(5, $exception->errorInfo[1]);
                $this->assertStringContainsString('database is locked', $exception->getMessage());
            } else {
                $this->assertSame(1205, $exception->errorInfo[1]);
                $this->assertStringContainsString('for update', strtolower($exception->getSql()));
                $this->assertStringContainsString('`users`', $exception->getSql());
                $this->assertStringContainsString('`'.$lockedColumn.'` = ?', $exception->getSql());
            }
        }
        $this->assertSame(0, DB::connection('concurrency_competitor')->transactionLevel());
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::purge('concurrency_competitor');
        }
    }
}
