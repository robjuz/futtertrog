<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class LoginWithGitlabTest extends TestCase
{
    use WithFaker;

    protected function mockSocialite(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');

        $abstractUser->shouldReceive('getId')
            ->andReturn($this->faker->uuid)
            ->shouldReceive('getName')
            ->andReturn($this->faker->name)
            ->shouldReceive('getEmail')
            ->andReturn('test@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn($this->faker->imageUrl());

        Socialite::shouldReceive('driver->user')->andReturn($abstractUser);
    }

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
        $this->mockSocialite();

        Config::set('services.gitlab.enabled', true);

        $this->assertDatabaseCount('users', 0);

        $this->get(route('login.gitlab-callback'));

        $this->assertDatabaseCount('users', 1);

        $this->assertAuthenticatedAs(User::whereEmail('test@example.com')->first());
    }


    /** @test * */
    public function it_logs_in_a_user_with_the_equivalent_gitlab_email()
    {
        $this->mockSocialite();

        Config::set('services.gitlab.enabled', true);

        $user = User::factory()->create(
            [
                'email' => 'test@example.com'
            ]
        );

        $this->assertDatabaseCount('users', 1);

        $this->get(route('login.gitlab-callback'));

        $this->assertDatabaseCount('users', 1);

        $this->assertAuthenticatedAs($user->first());
    }

    /** @test * */
    public function it_is_forbidden_to_login_in_with_deleted_account()
    {
        $this->mockSocialite();

        Config::set('services.gitlab.enabled', true);

        $user = User::factory()->create(
            [
                'email' => 'test@example.com'
            ]
        );

        $user->delete();

        $this->withExceptionHandling()
            ->get(route('login.gitlab-callback'))
            ->assertUnauthorized();
    }
}
