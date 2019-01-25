<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function login($user = null)
    {
        $user = $user ? : factory('App\User')->create();

        $this->actingAs($user);

        return $this;
    }
}
