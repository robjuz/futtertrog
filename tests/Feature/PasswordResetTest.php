<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    #[Test]
    public function guest_can_obtain_a_password_reset_link()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'john@example.com']);

        $this->post(route('password.email'), ['email' => 'john@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function guest_can_reset_his_password()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $broker = Password::broker();

        $this->post(route('password.update'), [
            'token' => $broker->createToken($user),
            'email' => 'john@example.com',
            'password' => 'new_password',
            'password_confirmation' => 'new_password',
        ]);

        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));

    }
}
