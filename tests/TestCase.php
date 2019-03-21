<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public function loginAsAdmin()
    {
        return $this->login(factory('App\User')->create(['is_admin' => true]));
    }

    public function login($user = null)
    {
        $user = $user ?: factory('App\User')->create();

        $this->actingAs($user);

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver === 'sqlite') {
            DB::statement('select load_extension(“json1”)');


        }


        $this->withoutExceptionHandling();
    }
}
