<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{

   /** @test */
   public function it_allows_to_login_with_email_and_password()
   {
        factory('App\User')->create([
            'name' => 'John Doe',
            'email' => 'john-doe@example.com'
        ]);

        $this->post(route('login'), [
            'email' => 'john-doe@example.com',
            'password' => 'secret'
        ]);

       $this->assertAuthenticated();
   }
}
