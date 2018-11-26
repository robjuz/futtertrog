<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
}
