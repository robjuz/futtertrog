<?php

namespace Tests\Console\Commands;

use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{

    public function testHandle()
    {
        $this->artisan('futtertrog:create-admin admin admin@example.com 12345')->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'admin',
            'email' => 'admin@example.com',
            'is_admin' => 1
        ]);

        $this->artisan('futtertrog:create-admin')
            ->expectsQuestion('name','admin2')
            ->expectsQuestion('email', 'admin2@example.com')
            ->expectsQuestion('password', '12345')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'admin2',
            'email' => 'admin2@example.com',
            'is_admin' => 1
        ]);
    }
}
