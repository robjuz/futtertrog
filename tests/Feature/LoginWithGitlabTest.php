<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\CredentialConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class LoginWithGitlabTest extends TestCase
{
    use WithFaker;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_a_new_user_from_gitlab_user()
    {
        $this->mockSocialite();

        $this->assertDatabaseCount('users', 0);

        $this->get(route('login.oauth-callback', 'gitlab'));

        $this->assertDatabaseCount('users', 1);

        $this->assertAuthenticatedAs(User::whereEmail('test@example.com')->first());
    }

    protected function mockSocialite(): self
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

        return $this;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_confirmation_for_an_equivalent_gitlab_email()
    {
        Mail::fake();
        $this->mockSocialite();

        $user = User::factory()->create(
            [
                'email' => 'test@example.com'
            ]
        );

        $this->assertDatabaseCount('users', 1);

        $this->get(route('login.oauth-callback', 'gitlab'));

        $this->assertDatabaseCount('users', 1);

        $this->assertGuest();
        $this->assertDatabaseCount('external_identities', 0);
        Mail::assertSent(CredentialConfirmation::class, fn ($mail) => $mail->hasTo($user->email));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_is_forbidden_to_login_in_with_deleted_account()
    {
        Mail::fake();
        $this->mockSocialite();

        $user = User::factory()->create(
            [
                'email' => 'test@example.com'
            ]
        );

        $user->delete();

        $this->withExceptionHandling()
            ->get(route('login.oauth-callback', 'gitlab'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
        Mail::assertNothingSent();
    }
}
