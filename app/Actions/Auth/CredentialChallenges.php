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
    public const PUBLIC_MESSAGE = 'Falls eine Bestätigung möglich ist, wurde eine E-Mail an die gespeicherte Adresse gesendet. Ohne Zugriff auf diese Adresse wenden Sie sich bitte an den bestehenden Support.';
    public const INVALID_MESSAGE = 'Die Bestätigung ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.';

    public static function providerAllowed(string $provider): bool
    {
        return in_array($provider, config('services.oauth_providers', []), true);
    }

    public function send(User $user, string $purpose, ?string $provider = null, ?string $subject = null): void
    {
        if ($user->trashed() || ! in_array($purpose, [
            CredentialChallenge::SET_PASSWORD, CredentialChallenge::LEGACY_PASSWORD,
            CredentialChallenge::RESET_PASSWORD, CredentialChallenge::LINK_IDENTITY,
        ], true)) {
            return;
        }

        if ($this->hasInvalidIdentityLink($purpose, $provider, $subject)) {
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

    private function hasInvalidIdentityLink(string $purpose, ?string $provider, ?string $subject): bool
    {
        return $purpose === CredentialChallenge::LINK_IDENTITY &&
            (! $provider || ! self::providerAllowed($provider) || ! $subject || strlen($subject) > 255);
    }

    public function find(int $userId, string $purpose, string $token): CredentialChallenge
    {
        $challenge = CredentialChallenge::where('user_id', $userId)->where('purpose', $purpose)
            ->where('token_hash', hash('sha256', $token))->whereNull('consumed_at')
            ->where('expires_at', '>', now())->first();

        if (! $challenge || ! $challenge->user || $challenge->user->trashed()) {
            throw ValidationException::withMessages(['confirmation' => self::INVALID_MESSAGE]);
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
                    throw ValidationException::withMessages(['confirmation' => self::INVALID_MESSAGE]);
                }

                if ($purpose === CredentialChallenge::LINK_IDENTITY) {
                    $this->linkIdentity($user, $challenge);

                    return $user;
                }

                $this->replacePassword($user, $purpose, $password);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['confirmation' => self::INVALID_MESSAGE]);
        }
    }

    private function linkIdentity(User $user, CredentialChallenge $challenge): void
    {
        if (! self::providerAllowed($challenge->pending_provider ?? '') ||
            ! $challenge->pending_subject || ExternalIdentity::where('provider', $challenge->pending_provider)
                ->where('subject', $challenge->pending_subject)->exists()) {
            throw ValidationException::withMessages(['confirmation' => self::INVALID_MESSAGE]);
        }
        ExternalIdentity::create([
            'user_id' => $user->id, 'provider' => $challenge->pending_provider,
            'subject' => $challenge->pending_subject,
        ]);
    }

    private function replacePassword(User $user, string $purpose, ?string $password): void
    {
        if (! in_array($purpose, [CredentialChallenge::SET_PASSWORD, CredentialChallenge::LEGACY_PASSWORD,
            CredentialChallenge::RESET_PASSWORD], true) || $password === null ||
            ($purpose === CredentialChallenge::SET_PASSWORD && $user->local_credential_status === 'enabled') ||
            ($purpose === CredentialChallenge::LEGACY_PASSWORD && $user->local_credential_status !== 'legacy')) {
            throw ValidationException::withMessages(['confirmation' => self::INVALID_MESSAGE]);
        }
        app(LocalPassword::class)->replace($user, $password);
        $user->credentialChallenges()->whereNull('consumed_at')->update(['consumed_at' => now()]);
    }
}
