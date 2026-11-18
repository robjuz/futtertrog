<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    public function loginAsAdmin()
    {
        return $this->login(User::factory()->admin()->create());
    }

    public function login($user = null)
    {
        $user = $user ?: User::factory()->create();

        $this->actingAs($user);

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    /**
     * Give commit/upgrade tests an isolated database on the selected suite driver.
     * Provision through a separate connection: MariaDB DDL implicitly commits.
     *
     * @param list<string> $aliases
     */
    protected function createFixtureDatabase(string $name, array $aliases = []): void
    {
        $original = config('database.default');
        $connection = DB::connection()->getConfig();
        unset($connection['name']);
        $connection['url'] = null;
        $connections = [$name, ...$aliases];
        $provisioner = $name.'_provisioner';
        $isSqlite = $connection['driver'] === 'sqlite';

        if ($isSqlite) {
            $database = tempnam(sys_get_temp_dir(), 'futtertrog-fixture-');
        } else {
            $database = 'futtertrog_fixture_'.bin2hex(random_bytes(8));
            config(['database.connections.'.$provisioner => $connection]);
            DB::connection($provisioner)->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }

        $connection['database'] = $database;
        foreach ($connections as $alias) {
            config(['database.connections.'.$alias => $connection]);
        }

        $this->beforeApplicationDestroyed(function () use ($connections, $original, $database, $isSqlite, $provisioner): void {
            DB::setDefaultConnection($original);
            foreach ($connections as $alias) {
                DB::purge($alias);
            }
            if ($isSqlite) {
                unlink($database);
            } else {
                try {
                    DB::connection($provisioner)->statement('DROP DATABASE `'.$database.'`');
                } finally {
                    DB::purge($provisioner);
                }
            }
        });
    }

    protected function onConnection(string $name, \Closure $operation): mixed
    {
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection($name);
        try {
            return $operation();
        } finally {
            DB::setDefaultConnection($original);
        }
    }
}
