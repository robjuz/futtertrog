<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    /** @test */
    public function guest_can_obtain_a_password_reset_link()
    {
        Notification::fake();

        $user = factory(User::class)->create(['email' => 'john@example.com']);

        $this->post(route('password.email'), ['email' => 'john@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function guest_can_reset_his_password()
    {
        $user = factory(User::class)->create(['email' => 'john@example.com']);

        $broker = Password::broker();

        $this->post(route('password.update'), [
            'token' => $broker->createToken($user),
            'email' => 'john@example.com',
            'password' => 'new_password',
            'password_confirmation' => 'new_password'
        ]);

        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));

    }
}
