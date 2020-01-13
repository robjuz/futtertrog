<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /** @test */
    public function it_redirects_to_login_page_after_logout()
    {
        $this->login()->post(route('logout'))->assertLocation(route('login'));
    }
}
