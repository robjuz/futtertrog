<?php

namespace Tests\Feature;

use App\Actions\Auth\CredentialChallenges;
use App\Mail\CredentialConfirmation;
use App\Models\CredentialChallenge;
use App\Models\ExternalIdentity;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as ProviderRequest;
use GuzzleHttp\Psr7\Response as ProviderResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GitlabProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuthUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CredentialChallengeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function oauth(string $subject, string $email): void
    {
        Socialite::shouldReceive('driver->user')->once()->andReturn((new OAuthUser)->map([
            'id' => $subject, 'email' => $email, 'name' => 'External name',
        ]));
    }

    private function mailedUrl(User $user, string $purpose): string
    {
        $url = '';
        Mail::assertSent(CredentialConfirmation::class, function (CredentialConfirmation $mail) use ($user, $purpose, &$url): bool {
            $url = $mail->confirmationUrl;
            return $mail->hasTo($user->email) && $mail->purpose === $purpose;
        });
        return $url;
    }

    private function password(): array
    {
        return ['password' => 'confirmed-new-password', 'password_confirmation' => 'confirmed-new-password'];
    }

    #[Test]
    public function legacy_login_requires_mail_and_explicit_new_password_before_full_session(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret'])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSame('legacy', $user->fresh()->local_credential_status);
        $this->assertTrue(Hash::check('secret', $user->fresh()->password));
        $url = $this->mailedUrl($user, CredentialChallenge::LEGACY_PASSWORD);
        $token = basename($url);
        $challenge = CredentialChallenge::sole();
        $this->assertSame(hash('sha256', $token), $challenge->token_hash);
        $this->assertLessThanOrEqual(900, now()->diffInSeconds($challenge->expires_at));
        $this->get($url)->assertOk()->assertDontSee($token, false);
        $this->assertNull($challenge->fresh()->consumed_at);
        $this->post($url, $this->password())->assertRedirect('/profile/security');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('enabled', $user->fresh()->local_credential_status);
        $this->assertFalse(Hash::check('secret', $user->fresh()->password));
        $this->assertTrue(Hash::check('confirmed-new-password', $user->fresh()->password));
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'confirmed-new-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function incorrect_legacy_password_sends_no_confirmation(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->assertGuest();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('credential_challenges', 0);
    }

    #[Test]
    public function external_account_can_add_password_without_losing_external_login(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->disabled()->create();
        ExternalIdentity::factory()->for($user)->create(['subject' => 'existing-subject']);
        $this->actingAs($user)->post('/profile/password/confirmation')->assertRedirect();
        $url = $this->mailedUrl($user, CredentialChallenge::SET_PASSWORD);
        $this->assertSame('disabled', $user->fresh()->local_credential_status);
        $this->post($url, $this->password())->assertRedirect('/profile/security');
        $this->assertSame('enabled', $user->fresh()->local_credential_status);
        $this->assertDatabaseCount('external_identities', 1);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'confirmed-new-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $this->oauth('existing-subject', 'changed-provider-email@example.com');
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function external_legacy_transition_uses_stored_mailbox_and_keeps_existing_hash(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        $this->oauth('new-subject', $user->email);
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseCount('external_identities', 0);
        $this->post($this->mailedUrl($user, CredentialChallenge::LINK_IDENTITY))->assertRedirect('/profile/security');
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame('legacy', $user->fresh()->local_credential_status);
        $this->assertDatabaseHas('external_identities', ['user_id' => $user->id, 'provider' => 'gitlab', 'subject' => 'new-subject']);
    }

    #[Test]
    public function stored_provider_subject_wins_over_a_different_accounts_email(): void
    {
        Mail::fake();
        $this->freezeTime();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        ExternalIdentity::factory()->for($owner)->create(['subject' => 'stable-subject']);
        $this->oauth('stable-subject', $other->email);
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/');
        $this->assertAuthenticatedAs($owner);
        $this->assertSame($owner->email, $owner->fresh()->email);
        Mail::assertNothingSent();
    }

    #[Test]
    public function authenticated_linking_also_requires_fresh_confirmation_at_stored_address(): void
    {
        Mail::fake();
        $this->freezeTime();
        $owner = User::factory()->enabled()->create();
        $this->actingAs($owner);
        $this->oauth('additional-subject', 'provider-address@example.com');
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/profile/security');
        $this->assertDatabaseCount('external_identities', 0);
        $this->post($this->mailedUrl($owner, CredentialChallenge::LINK_IDENTITY))->assertRedirect('/profile/security');
        $this->assertDatabaseHas('external_identities', ['user_id' => $owner->id, 'subject' => 'additional-subject']);
        $this->assertSame('enabled', $owner->fresh()->local_credential_status);
        $this->assertSame($owner->password, $owner->fresh()->password);
    }

    #[Test]
    public function linking_a_subject_owned_by_another_account_never_switches_or_merges_accounts(): void
    {
        Mail::fake();
        $this->freezeTime();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        ExternalIdentity::factory()->for($other)->create(['subject' => 'collision']);
        $this->actingAs($owner);
        $this->oauth('collision', $owner->email);
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/profile/security');
        $this->assertAuthenticatedAs($owner);
        $this->assertDatabaseCount('external_identities', 1);
        $this->assertDatabaseHas('external_identities', ['user_id' => $other->id, 'subject' => 'collision']);
        Mail::assertNothingSent();
    }

    #[Test]
    public function a_collision_after_mail_issuance_is_rejected_without_consuming_or_merging(): void
    {
        Mail::fake();
        $this->freezeTime();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $challenge = CredentialChallenge::factory()->for($owner)->linking()->create(['token_hash' => hash('sha256', 'proof')]);
        ExternalIdentity::factory()->for($other)->create(['provider' => $challenge->pending_provider, 'subject' => $challenge->pending_subject]);
        $this->postJson("/credentials/$owner->id/link-identity/proof")->assertUnprocessable();
        $this->assertDatabaseCount('external_identities', 1);
        $this->assertNull($challenge->fresh()->consumed_at);
        $this->assertGuest();
    }

    #[Test]
    #[DataProvider('invalidProofs')]
    public function invalid_proofs_never_change_password_or_identity(string $failure, string $purpose): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        $other = User::factory()->create();
        $challenge = CredentialChallenge::factory()->for($user)->create([
            'purpose' => $purpose, 'token_hash' => hash('sha256', 'original-proof'),
            'pending_provider' => $purpose === 'link-identity' ? 'gitlab' : null,
            'pending_subject' => $purpose === 'link-identity' ? 'pending-subject' : null,
            'expires_at' => $failure === 'expired' ? now()->subSecond() : now()->addMinutes(15),
            'consumed_at' => $failure === 'reused' ? now() : null,
        ]);
        $account = $failure === 'account' ? $other->id : $user->id;
        $requestedPurpose = $failure === 'purpose' ? 'unrelated-purpose' : $purpose;
        $this->postJson("/credentials/$account/$requestedPurpose/original-proof", $purpose === 'link-identity' ? [] : $this->password())
            ->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame('legacy', $user->fresh()->local_credential_status);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertGuest();
    }

    public static function invalidProofs(): array
    {
        $cases = [];
        foreach (['set-password', 'reset-password', 'legacy-password', 'link-identity'] as $purpose) {
            foreach (['expired', 'reused', 'account', 'purpose'] as $failure) {
                $cases["$purpose $failure"] = [$failure, $purpose];
            }
        }
        return $cases;
    }

    #[Test]
    public function an_authenticated_different_account_cannot_redeem_a_valid_proof(): void
    {
        Mail::fake();
        $this->freezeTime();
        $owner = User::factory()->legacy()->create();
        CredentialChallenge::factory()->for($owner)->create(['token_hash' => hash('sha256', 'proof')]);
        $other = User::factory()->create();
        $this->actingAs($other)->postJson("/credentials/$owner->id/legacy-password/proof", $this->password())->assertUnprocessable();
        $this->assertSame($owner->password, $owner->fresh()->password);
        $this->assertAuthenticatedAs($other);
    }

    #[Test]
    public function a_successfully_redeemed_link_cannot_be_replayed(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->create();
        CredentialChallenge::factory()->for($user)->linking()->create(['token_hash' => hash('sha256', 'proof')]);
        $this->post("/credentials/$user->id/link-identity/proof")->assertRedirect('/profile/security');
        $this->postJson("/credentials/$user->id/link-identity/proof")->assertUnprocessable();
        $this->assertDatabaseCount('external_identities', 1);
    }

    #[Test]
    public function valid_proofs_for_different_purposes_cannot_be_exchanged(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        CredentialChallenge::factory()->for($user)->create(['token_hash' => hash('sha256', 'legacy-proof')]);
        CredentialChallenge::factory()->for($user)->create([
            'purpose' => CredentialChallenge::RESET_PASSWORD, 'token_hash' => hash('sha256', 'reset-proof'),
        ]);
        $this->postJson("/credentials/$user->id/reset-password/legacy-proof", $this->password())->assertUnprocessable();
        $this->postJson("/credentials/$user->id/legacy-password/reset-proof", $this->password())->assertUnprocessable();
        $this->assertSame($user->password, $user->fresh()->password);
    }

    #[Test]
    public function challenge_mail_is_rate_limited_across_purposes_and_never_contains_a_password(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret']);
        $this->post('/password/email', ['email' => $user->email]);
        Mail::assertSentCount(1);
        $mail = Mail::sent(CredentialConfirmation::class)->sole();
        $this->assertStringNotContainsString('secret', $mail->render());
        $this->assertDatabaseCount('credential_challenges', 1);
        $this->travel(61)->seconds();
        $this->post('/password/email', ['email' => $user->email]);
        Mail::assertSentCount(2);
    }

    #[Test]
    public function invalid_oauth_state_and_unknown_provider_make_no_changes(): void
    {
        Mail::fake();
        $this->freezeTime();
        Socialite::shouldReceive('driver->user')->once()->andThrow(new InvalidStateException);
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/login');
        $this->get('/login/oauth/unlisted')->assertNotFound();
        $this->get('/login/oauth/unlisted/callback')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('external_identities', 0);
        Mail::assertNothingSent();
    }

    private function deniedOauthProvider(): MockHandler
    {
        $handler = new MockHandler([new ProviderResponse(400, [], '{"error":"invalid_grant"}')]);
        $client = new Client(['handler' => HandlerStack::create($handler)]);
        Socialite::shouldReceive('driver')->once()->with('gitlab')->andReturnUsing(
            fn (): GitlabProvider => (new GitlabProvider(request(), 'client', 'secret', '/callback'))->setHttpClient($client)
        );
        return $handler;
    }

    #[Test]
    #[DataProvider('oauthFailureLanguages')]
    public function denied_oauth_grant_returns_to_login_with_a_visible_message_and_no_changes(string $locale, string $message): void
    {
        Mail::fake();
        Log::spy();
        app()->setLocale($locale);
        $handler = $this->deniedOauthProvider();

        $this->withSession(['state' => 'valid-state'])
            ->get('/login/oauth/gitlab/callback?error=access_denied&state=valid-state')
            ->assertRedirect('/login')->assertSessionHas('status', $message)->assertSessionMissing('state');

        $this->assertSame(0, $handler->count());
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertDatabaseCount('credential_challenges', 0);
        Mail::assertNothingSent();
        Log::shouldNotHaveReceived('warning');
        $this->get('/login')->assertOk()->assertSeeText($message);
    }

    public static function oauthFailureLanguages(): array
    {
        return [
            'German' => ['de', 'Die Anmeldung beim Anbieter wurde abgebrochen oder ist fehlgeschlagen. Bitte versuchen Sie es erneut.'],
            'English' => ['en', 'Sign-in with the provider was cancelled or failed. Please try again.'],
        ];
    }

    #[Test]
    public function denied_oauth_grant_with_invalid_state_keeps_the_existing_state_response(): void
    {
        Mail::fake();
        Log::spy();
        $handler = $this->deniedOauthProvider();

        $this->withSession(['state' => 'valid-state'])
            ->get('/login/oauth/gitlab/callback?error=access_denied&state=wrong-state')
            ->assertRedirect('/login')->assertSessionHas('status', CredentialChallenges::PUBLIC_MESSAGE);

        $this->assertSame(1, $handler->count());
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertDatabaseCount('credential_challenges', 0);
        Mail::assertNothingSent();
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function oauth_error_response_cannot_create_an_account_even_if_user_retrieval_succeeds(): void
    {
        Mail::fake();
        app()->setLocale('de');
        $this->oauth('unused-subject', 'unused@example.com');

        $this->get('/login/oauth/gitlab/callback?error=server_error')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Die Anmeldung beim Anbieter wurde abgebrochen oder ist fehlgeschlagen. Bitte versuchen Sie es erneut.');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertDatabaseCount('credential_challenges', 0);
        Mail::assertNothingSent();
    }

    #[Test]
    #[DataProvider('oauthProviderFailures')]
    public function oauth_provider_failure_returns_to_security_with_visible_message_and_safe_logging(GuzzleException $exception, ?int $status): void
    {
        Mail::fake();
        Log::spy();
        $user = User::factory()->enabled()->create(['settings' => ['language' => 'de']]);
        $attributes = $user->fresh()->getRawOriginal();
        $sessionId = session()->getId();
        $message = 'Die Anmeldung beim Anbieter wurde abgebrochen oder ist fehlgeschlagen. Bitte versuchen Sie es erneut.';
        Socialite::shouldReceive('driver->user')->once()->andThrow($exception);

        $this->actingAs($user)->withCookie(config('session.cookie'), $sessionId)
            ->get('/login/oauth/gitlab/callback?code=secret-code')
            ->assertRedirect('/profile/security')->assertSessionHas('status', $message);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($sessionId, session()->getId());
        $this->assertSame($attributes, $user->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertDatabaseCount('credential_challenges', 0);
        Mail::assertNothingSent();
        Log::shouldHaveReceived('warning')->once()->with('OAuth provider request failed.', [
            'provider' => 'gitlab', 'exception' => $exception::class, 'status' => $status,
        ]);
        $this->get('/profile/security')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Profile/Security')->where('flash.status', $message));
    }

    #[Test]
    #[DataProvider('oauthProviderFailures')]
    public function oauth_provider_failure_returns_json_without_changing_the_authenticated_account(GuzzleException $exception, ?int $status): void
    {
        Mail::fake();
        Log::spy();
        $user = User::factory()->enabled()->create(['settings' => ['language' => 'en']]);
        $attributes = $user->fresh()->getRawOriginal();
        $sessionId = session()->getId();
        Socialite::shouldReceive('driver->user')->once()->andThrow($exception);

        $this->actingAs($user)->withCookie(config('session.cookie'), $sessionId)->withCredentials()
            ->getJson('/login/oauth/gitlab/callback?code=secret-code')
            ->assertOk()->assertExactJson(['message' => 'Sign-in with the provider was cancelled or failed. Please try again.'])
            ->assertSessionMissing('status');

        $this->assertAuthenticatedAs($user);
        $this->assertSame($sessionId, session()->getId());
        $this->assertSame($attributes, $user->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('external_identities', 0);
        $this->assertDatabaseCount('credential_challenges', 0);
        Mail::assertNothingSent();
        Log::shouldHaveReceived('warning')->once()->with('OAuth provider request failed.', [
            'provider' => 'gitlab', 'exception' => $exception::class, 'status' => $status,
        ]);
    }

    public static function oauthProviderFailures(): array
    {
        $request = new ProviderRequest('POST', 'https://provider.example/token?code=secret-code', [
            'Authorization' => 'Bearer secret-token',
        ], 'client_secret=secret-client&email=private@example.com');
        return [
            'transport' => [new ConnectException('secret-token private@example.com', $request), null],
            'HTTP 400' => [new ClientException('secret-code private@example.com', $request, new ProviderResponse(400, [], 'secret-response')), 400],
            'HTTP 503' => [new ServerException('secret-token private@example.com', $request, new ProviderResponse(503, [], 'secret-response')), 503],
        ];
    }

    #[Test]
    public function disabled_local_method_cannot_authenticate_even_with_a_matching_placeholder_hash(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->create(['local_credential_status' => 'disabled']);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret'])->assertUnprocessable();
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    #[Test]
    public function deactivated_accounts_cannot_login_receive_mail_or_redeem_existing_proofs(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->legacy()->create();
        ExternalIdentity::factory()->for($user)->create(['subject' => 'inactive-subject']);
        CredentialChallenge::factory()->for($user)->create(['token_hash' => hash('sha256', 'proof')]);
        $user->delete();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret'])->assertUnprocessable();
        $this->post('/password/email', ['email' => $user->email])->assertRedirect();
        $this->oauth('inactive-subject', 'changed@example.com');
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/login');
        $this->postJson("/credentials/$user->id/legacy-password/proof", $this->password())->assertUnprocessable();
        $this->assertGuest();
        Mail::assertNothingSent();
        $this->assertSame($user->password, User::withTrashed()->find($user->id)->password);
        $this->assertDatabaseCount('external_identities', 1);
    }

    #[Test]
    public function public_reset_has_identical_responses_for_missing_inactive_and_throttled_accounts(): void
    {
        Mail::fake();
        $this->freezeTime();
        $known = User::factory()->create();
        $inactive = User::factory()->trashed()->create();
        $responses = [];
        foreach ([$known->email, 'absent@example.com', $inactive->email, $known->email] as $email) {
            $responses[] = $this->postJson('/password/email', ['email' => $email])->assertOk()->json();
        }
        $this->assertSame([$responses[0], $responses[0], $responses[0], $responses[0]], $responses);
        Mail::assertSentCount(1);
    }

    #[Test]
    #[DataProvider('resetStates')]
    public function public_reset_uses_the_same_confirmed_setup_for_every_credential_state(string $state): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->create(['local_credential_status' => $state]);
        $this->post('/password/email', ['email' => $user->email])->assertRedirect();
        $url = $this->mailedUrl($user, CredentialChallenge::RESET_PASSWORD);
        $this->get($url)->assertOk();
        $this->postJson($url, ['password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
        $this->assertSame($user->password, $user->fresh()->password);
        $this->post($url, $this->password())->assertRedirect('/profile/security');
        $this->assertSame('enabled', $user->fresh()->local_credential_status);
        $this->assertTrue(Hash::check('confirmed-new-password', $user->fresh()->password));
        $this->postJson($url, $this->password())->assertUnprocessable();
    }

    public static function resetStates(): array
    {
        return ['legacy' => ['legacy'], 'enabled' => ['enabled'], 'disabled' => ['disabled']];
    }

    #[Test]
    public function oauth_access_and_refresh_tokens_are_never_persisted_or_returned(): void
    {
        Mail::fake();
        $oauth = (new OAuthUser)->map(['id' => 'new-subject', 'email' => 'new@example.com', 'name' => 'New user']);
        $oauth->setToken('private-access-token')->setRefreshToken('private-refresh-token');
        Socialite::shouldReceive('driver->user')->once()->andReturn($oauth);
        $this->getJson('/login/oauth/gitlab/callback')->assertExactJson(['authenticated' => true]);
        $user = User::whereEmail('new@example.com')->sole();
        $this->assertArrayNotHasKey('access_token', ExternalIdentity::sole()->getAttributes());
        $this->assertStringNotContainsString('private-access-token', $user->getRawOriginal('password'));
        $this->get('/profile/security')->assertDontSee('private-access-token', false)->assertDontSee('private-refresh-token', false);
        Mail::assertNothingSent();
    }

    #[Test]
    public function the_confirmation_must_match_and_a_twelve_character_password_is_accepted(): void
    {
        Mail::fake();
        $user = User::factory()->legacy()->create();
        CredentialChallenge::factory()->for($user)->create(['token_hash' => hash('sha256', 'proof')]);
        $url = "/credentials/$user->id/legacy-password/proof";
        $this->postJson($url, ['password' => '123456789012', 'password_confirmation' => 'different-pass'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame($user->password, $user->fresh()->password);
        $this->post($url, ['password' => '123456789012', 'password_confirmation' => '123456789012'])->assertRedirect('/profile/security');
        $this->assertTrue(Hash::check('123456789012', $user->fresh()->password));
        Mail::assertNothingSent();
    }
}
