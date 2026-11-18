<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileSessionTest extends TestCase
{
    private function browser(string $sessionId, string $method, string $uri, array $data = [], array $cookies = []): TestResponse
    {
        Auth::forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->defaultCookies = array_merge($cookies, [config('session.cookie') => $sessionId]);
        return $this->call($method, $uri, $data, $this->prepareCookiesForRequest());
    }

    #[Test]
    #[DataProvider('sessionDrivers')]
    public function password_change_keeps_current_browser_and_invalidates_other_browser_and_remember_cookie(string $driver): void
    {
        config(['session.driver' => $driver]);
        $this->withExceptionHandling();
        $user = User::factory()->enabled()->create();
        $beforeToken = $user->remember_token;
        $login = ['email' => $user->email, 'password' => 'secret', 'remember' => true];
        $a = $this->browser(Str::random(40), 'POST', '/login', $login)->assertRedirect('/');
        $sessionA = $this->app['session.store']->getId();
        $this->browser(Str::random(40), 'POST', '/login', $login)->assertRedirect('/');
        $sessionB = $this->app['session.store']->getId();
        $this->assertNotSame($sessionA, $sessionB);
        $this->browser($sessionB, 'GET', '/profile')->assertOk();
        $this->browser($sessionA, 'GET', '/profile')->assertOk();
        $recallerName = Auth::guard('web')->getRecallerName();
        $rememberCookie = $a->getCookie($recallerName)->getValue();

        $this->browser($sessionA, 'PUT', '/profile/password', [
            'current_password' => 'secret', 'password' => 'a-new-valid-password',
            'password_confirmation' => 'a-new-valid-password',
        ])->assertRedirect();
        $regeneratedA = $this->app['session.store']->getId();
        $this->assertNotSame($sessionA, $regeneratedA);
        $this->assertTrue(Hash::check('a-new-valid-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('secret', $user->fresh()->password));
        $this->assertNotSame($beforeToken, $user->fresh()->remember_token);
        $this->browser($regeneratedA, 'GET', '/profile')->assertOk();
        $this->browser($sessionB, 'GET', '/profile')->assertRedirect('/login');
        $this->browser(Str::random(40), 'GET', '/profile', [], [$recallerName => $rememberCookie])->assertRedirect('/login');
        $this->browser($regeneratedA, 'GET', '/profile')->assertOk();
    }

    #[Test]
    public function password_setup_also_invalidates_an_untouched_pre_migration_file_session(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['session.driver' => 'file']);
        $this->withExceptionHandling();
        $user = User::factory()->legacy()->create();
        $sessionA = Str::random(40);
        $sessionB = Str::random(40);
        foreach ([$sessionA, $sessionB] as $sessionId) {
            $this->app['session']->forgetDrivers();
            $store = $this->app['session']->driver();
            $store->setId($sessionId);
            $store->start();
            $store->put(Auth::guard('web')->getName(), $user->id);
            $store->save();
        }
        $this->browser($sessionA, 'GET', '/profile')->assertOk();
        $this->browser($sessionA, 'POST', '/profile/password/confirmation')->assertRedirect();
        $mail = \Illuminate\Support\Facades\Mail::sent(\App\Mail\CredentialConfirmation::class)->sole();
        $this->browser($sessionA, 'POST', $mail->confirmationUrl, [
            'password' => 'confirmed-new-password', 'password_confirmation' => 'confirmed-new-password',
        ])->assertRedirect('/profile/security');
        $current = $this->app['session.store']->getId();
        $this->browser($current, 'GET', '/profile')->assertOk();
        $this->browser($sessionB, 'GET', '/profile')->assertRedirect('/login');
        \Illuminate\Support\Facades\Mail::assertSentCount(1);
    }

    public static function sessionDrivers(): array
    {
        return ['database sessions' => ['database'], 'file sessions' => ['file']];
    }
}
