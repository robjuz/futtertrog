<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoginWithGitlabTest extends TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     */
    public function it_can_be_disabled()
    {
        Config::set('services.gitlab.enabled', false);

        $this->withExceptionHandling()
            ->get(route('login.gitlab'))
            ->assertNotFound();
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function it_can_be_enabled()
    {
        Config::set('services.gitlab.enabled', true);

        $this->get(route('login.gitlab'))->assertRedirect();
    }
}
