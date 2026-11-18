<?php

namespace Tests\Feature;

use App\Mail\CredentialConfirmation;
use App\Models\CredentialChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_can_obtain_a_password_reset_link(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'john@example.com']);
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        Mail::assertSent(CredentialConfirmation::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->purpose === CredentialChallenge::RESET_PASSWORD);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_can_reset_his_password(): void
    {
        Mail::fake();
        $user = User::factory()->legacy()->create(['email' => 'john@example.com']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $mail = Mail::sent(CredentialConfirmation::class)->sole();
        $this->post(route('password.update'), [
            'token' => basename($mail->confirmationUrl), 'email' => $user->email,
            'password' => 'new_valid_password', 'password_confirmation' => 'new_valid_password',
        ])->assertRedirect(route('profile.security'));
        $this->assertTrue(Hash::check('new_valid_password', $user->fresh()->password));
        $this->assertSame('enabled', $user->fresh()->local_credential_status);
        Mail::assertSentCount(1);
    }
}
