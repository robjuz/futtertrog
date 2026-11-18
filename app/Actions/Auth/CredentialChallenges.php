<?php

namespace App\Actions\Auth;

use App\Mail\CredentialConfirmation;
use App\Models\CredentialChallenge;
use App\Models\ExternalIdentity;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CredentialChallenges
{
    public const PublicMessage = 'Falls eine Bestätigung möglich ist, wurde eine E-Mail an die gespeicherte Adresse gesendet. Ohne Zugriff auf diese Adresse wenden Sie sich bitte an den bestehenden Support.';
    public const InvalidMessage = 'Die Bestätigung ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.';

    public static function providerAllowed(string $provider): bool
    {
        return in_array($provider, config('services.oauth_providers', []), true);
    }

    public function send(User $user, string $purpose, ?string $provider = null, ?string $subject = null): void
    {
        if ($user->trashed() || ! in_array($purpose, [
            CredentialChallenge::SetPassword, CredentialChallenge::LegacyPassword,
            CredentialChallenge::ResetPassword, CredentialChallenge::LinkIdentity,
        ], true)) {
            return;
        }

        if ($purpose === CredentialChallenge::LinkIdentity &&
            (! $provider || ! self::providerAllowed($provider) || ! $subject || strlen($subject) > 255)) {
            return;
        }

        $key = 'credential-mail:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return;
        }
        RateLimiter::hit($key, 60);

        $token = bin2hex(random_bytes(32));
        $challenge = DB::transaction(function () use ($user, $purpose, $provider, $subject, $token): ?CredentialChallenge {
            $account = User::withTrashed()->lockForUpdate()->find($user->id);
            if (! $account || $account->trashed()) {
                return null;
            }
            $account->credentialChallenges()->where('purpose', $purpose)->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return $account->credentialChallenges()->create([
                'purpose' => $purpose,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(15),
                'pending_provider' => $provider,
                'pending_subject' => $subject,
            ]);
        });

        if ($challenge) {
            Mail::to($user->email)->send(new CredentialConfirmation(route('credentials.show', [
                'user' => $user->id, 'purpose' => $purpose, 'token' => $token,
            ]), $purpose));
        }
    }

    public function find(int $userId, string $purpose, string $token): CredentialChallenge
    {
        $challenge = CredentialChallenge::where('user_id', $userId)->where('purpose', $purpose)
            ->where('token_hash', hash('sha256', $token))->whereNull('consumed_at')
            ->where('expires_at', '>', now())->first();

        if (! $challenge || ! $challenge->user || $challenge->user->trashed()) {
            throw ValidationException::withMessages(['confirmation' => self::InvalidMessage]);
        }

        return $challenge;
    }

    public function consume(int $userId, string $purpose, string $token, ?string $password): User
    {
        try {
            return DB::transaction(function () use ($userId, $purpose, $token, $password): User {
                $user = User::withTrashed()->lockForUpdate()->find($userId);
                $challenge = $this->find($userId, $purpose, $token);
                $consumed = CredentialChallenge::whereKey($challenge->id)->whereNull('consumed_at')
                    ->where('expires_at', '>', now())->update(['consumed_at' => now()]);
                if (! $user || $user->trashed() || $consumed !== 1) {
                    throw ValidationException::withMessages(['confirmation' => self::InvalidMessage]);
                }

                if ($purpose === CredentialChallenge::LinkIdentity) {
                    if (! self::providerAllowed($challenge->pending_provider ?? '') ||
                        ! $challenge->pending_subject || ExternalIdentity::where('provider', $challenge->pending_provider)
                            ->where('subject', $challenge->pending_subject)->exists()) {
                        throw ValidationException::withMessages(['confirmation' => self::InvalidMessage]);
                    }
                    ExternalIdentity::create([
                        'user_id' => $user->id, 'provider' => $challenge->pending_provider,
                        'subject' => $challenge->pending_subject,
                    ]);
                } else {
                    if (! in_array($purpose, [CredentialChallenge::SetPassword, CredentialChallenge::LegacyPassword,
                        CredentialChallenge::ResetPassword], true) || $password === null ||
                        ($purpose === CredentialChallenge::SetPassword && $user->local_credential_status === 'enabled') ||
                        ($purpose === CredentialChallenge::LegacyPassword && $user->local_credential_status !== 'legacy')) {
                        throw ValidationException::withMessages(['confirmation' => self::InvalidMessage]);
                    }
                    app(LocalPassword::class)->replace($user, $password);
                    $user->credentialChallenges()->whereNull('consumed_at')->update(['consumed_at' => now()]);
                }

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['confirmation' => self::InvalidMessage]);
        }
    }
}
