<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    #[Test]
    public function matching_provider_email_requires_confirmation_before_login(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $hash = $user->password;
        Socialite::shouldReceive('driver->user')->once()->andReturn((new OAuthUser)->map([
            'id' => 'provider-subject', 'email' => $user->email, 'name' => 'Provider name',
        ]));

        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('users', 1);
        Mail::assertSentCount(1);
    }

    #[Test]
    public function new_external_accounts_cannot_use_the_provider_subject_as_password(): void
    {
        Socialite::shouldReceive('driver->user')->once()->andReturn((new OAuthUser)->map([
            'id' => 'known-provider-subject', 'email' => 'new@example.com', 'name' => 'New user',
        ]));
        $this->get('/login/oauth/gitlab/callback')->assertRedirect('/');

        $user = User::whereEmail('new@example.com')->sole();
        $this->assertFalse(Hash::check('known-provider-subject', $user->password));
        $this->assertSame('disabled', $user->local_credential_status);
    }

    #[Test]
    public function public_reset_does_not_reveal_whether_an_account_exists(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'existing@example.com']);
        $known = $this->postJson('/password/email', ['email' => 'existing@example.com']);
        $unknown = $this->postJson('/password/email', ['email' => 'unknown@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json(), $unknown->json());
    }

    #[Test]
    public function profile_never_serializes_credentials_or_administrative_data(): void
    {
        $user = User::factory()->admin()->create();
        $response = $this->actingAs($user)->get('/profile');
        $response->assertOk();
        $response->assertDontSee($user->password, false)->assertDontSee($user->api_token, false)
            ->assertDontSee($user->remember_token, false);
    }

    #[Test]
    public function profile_preserves_email_and_all_existing_settings(): void
    {
        $settings = [
            'language' => 'en', 'mealPreferences' => 'rice, carrots', 'mealAversion' => 'fish',
            'newOrderPossibilityNotification' => true, 'noOrderNotification' => true,
            'noOrderForNextDayNotification' => true, 'noOrderForNextWeekNotification' => true,
            'hideDashboardMealDescription' => true, 'hideOrderingMealDescription' => true,
            'redirectToNextDay' => true, 'retainedFuturePreference' => 'unchanged',
        ];
        $user = User::factory()->create(['settings' => $settings]);
        $this->actingAs($user)->get('/profile')->assertInertia(fn ($page) => $page
            ->component('Profile/Account')->where('account.name', $user->name)->where('account.email', $user->email));
        $this->patch('/profile', ['name' => 'Updated name'])->assertRedirect();
        $this->patch('/profile/preferences', ['mealPreferences' => 'potatoes', 'language' => 'de'])->assertRedirect();

        $expected = array_replace($settings, ['mealPreferences' => 'potatoes', 'language' => 'de']);
        $this->assertSame($expected, json_decode($user->fresh()->getRawOriginal('settings'), true));
        $this->assertSame('Updated name', $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->get('/profile/preferences')->assertInertia(fn ($page) => $page->component('Profile/Preferences')
            ->where('settings.mealAversion', 'fish')->where('settings.noOrderNotification', true));
        $this->get('/settings')->assertOk();
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenProfileFields')]
    public function profile_rejects_foreign_ids_roles_and_email_changes(string $endpoint, string $field): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->withExceptionHandling()->patchJson($endpoint, [
            'name' => 'Tampered', $field => $other->id,
        ])->assertUnprocessable();
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public static function forbiddenProfileFields(): array
    {
        return [
            'account user id' => ['/profile', 'user_id'], 'account id' => ['/profile', 'id'],
            'account role' => ['/profile', 'is_admin'], 'account roles' => ['/profile', 'roles'],
            'account email' => ['/profile', 'email'], 'credential status' => ['/profile', 'local_credential_status'],
            'preferences foreign id' => ['/profile/preferences', 'user_id'],
            'preferences role' => ['/profile/preferences', 'is_admin'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPasswords')]
    public function invalid_password_change_leaves_credentials_unchanged(array $changes): void
    {
        $user = User::factory()->enabled()->create();
        $before = $user->password;
        $this->actingAs($user)->withExceptionHandling()->putJson('/profile/password', array_replace([
            'current_password' => 'secret', 'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ], $changes))->assertUnprocessable()->assertDontSee($before, false);
        $this->assertSame($before, $user->fresh()->password);
        $this->assertSame($user->remember_token, $user->fresh()->remember_token);
    }

    public static function invalidPasswords(): array
    {
        return [
            'wrong current password' => [['current_password' => 'incorrect']],
            'short new password' => [['password' => '12345678901', 'password_confirmation' => '12345678901']],
            'confirmation mismatch' => [['password_confirmation' => 'different-value']],
            'foreign id' => [['user_id' => 999]], 'role' => [['is_admin' => true]],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('unconfirmedStates')]
    public function unconfirmed_credentials_cannot_change_password_directly(string $state): void
    {
        $user = User::factory()->create(['local_credential_status' => $state]);
        $this->actingAs($user)->withExceptionHandling()->putJson('/profile/password', [
            'current_password' => 'secret', 'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertForbidden();
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public static function unconfirmedStates(): array
    {
        return ['legacy' => ['legacy'], 'disabled' => ['disabled']];
    }

    #[Test]
    public function security_props_are_explicit_and_profile_assets_do_not_leak_into_blade(): void
    {
        $user = User::factory()->admin()->create();
        \App\Models\ExternalIdentity::factory()->for($user)->create(['subject' => 'private-subject']);
        $challenge = \App\Models\CredentialChallenge::factory()->for($user)->create();
        $this->actingAs($user)->get('/profile/security')->assertInertia(fn ($page) => $page
            ->component('Profile/Security')->where('identities', [['provider' => 'gitlab']])
            ->where('localCredentialStatus', 'enabled')->where('navigation.admin', '/admin/overview')
            ->missing('user')->missing('auth.user'))
            ->assertDontSee('private-subject', false)->assertDontSee($user->api_token, false)
            ->assertDontSee($user->password, false)->assertDontSee($user->remember_token, false)
            ->assertDontSee($challenge->token_hash, false)->assertDontSee('js/app.js', false)
            ->assertDontSee('css/app.css', false);
        $this->get('/meals')->assertOk()->assertDontSee('/build/assets/', false);
        $this->get('/admin')->assertOk()
            ->assertSee(\Illuminate\Support\Facades\Vite::asset('resources/css/admin.css'), false)
            ->assertDontSee(\Illuminate\Support\Facades\Vite::asset('resources/js/app.ts'), false);
        $normal = User::factory()->create();
        $this->actingAs($normal)->withSession(['password_hash_web' => $normal->password])
            ->get('/profile')->assertInertia(fn ($page) => $page->where('navigation.admin', null));
    }

    #[Test]
    public function json_login_returns_no_user_model_or_secrets(): void
    {
        $user = User::factory()->enabled()->create();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secret'])
            ->assertExactJson(['authenticated' => true]);
    }

    #[Test]
    public function selected_language_is_shared_with_the_profile_without_resetting_other_settings(): void
    {
        $user = User::factory()->create(['settings' => ['language' => 'en', 'mealAversion' => 'fish']]);
        $this->actingAs($user)->get('/profile')->assertInertia(fn ($page) => $page
            ->where('translations.Konto', 'Account')->where('translations.Einstellungen', 'Preferences'));
        $this->patch('/profile/preferences', ['language' => 'de'])->assertRedirect();
        $this->get('/profile')->assertInertia(fn ($page) => $page->where('translations.Konto', 'Konto'));
        $this->assertSame('fish', $user->fresh()->settings->mealAversion);
    }

    #[Test]
    public function guests_and_deactivated_accounts_cannot_open_profile_sections(): void
    {
        $this->withExceptionHandling();
        foreach (['/profile', '/profile/security', '/profile/preferences'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $inactive = User::factory()->create();
        $this->actingAs($inactive);
        $inactive->delete();
        foreach (['/profile', '/profile/security', '/profile/preferences'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    #[Test]
    public function profile_never_contacts_an_ssr_service_even_when_package_defaults_enable_it(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);
        $this->actingAs(User::factory()->create())->get('/profile')->assertOk();
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
