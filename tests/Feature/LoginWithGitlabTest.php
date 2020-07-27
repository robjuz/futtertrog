<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LoginWithGitlabTest extends TestCase
{
    /** @test */
    public function it_can_be_disabled()
    {
        Config::set('services.gitlab.enabled', false);

        $this->withExceptionHandling()
            ->get(route('login.gitlab'))
            ->assertNotFound();
    }

    /** @test */
    public function it_can_be_enabled()
    {
        Config::set('services.gitlab.enabled', true);

        $this->get(route('login.gitlab'))->assertRedirect();
    }

    /** @test * */
    public function it_creates_a_new_user_from_gitlab_user()
    {
        $this->assertDatabaseCount('users', 0);
    }
}
