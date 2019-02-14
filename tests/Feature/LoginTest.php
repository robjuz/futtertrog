<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;

class LoginTest extends TestCase
{

    /** @test */
    public function it_allows_to_login_with_email_and_password()
    {
        factory(User::class)->create([
            'name' => 'John Doe',
            'email' => 'john-doe@example.com'
        ]);

        $this->post(route('login'), [
            'email' => 'john-doe@example.com',
            'password' => 'secret'
        ]);

        $this->assertAuthenticated();
    }

    /** @test */
    public function authenticated_users_cannot_access_the_login_page()
    {
        $this->login()
            ->get(route('login'))
            ->assertRedirect('/');
    }
}
