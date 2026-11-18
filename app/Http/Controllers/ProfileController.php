<?php

namespace App\Http\Controllers;

use App\Actions\Auth\LocalPassword;
use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfilePreferencesRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function account(Request $request): Response
    {
        return Inertia::render('Profile/Account', [
            'account' => ['name' => $request->user()->name, 'email' => $request->user()->email],
        ]);
    }

    public function security(Request $request): Response
    {
        return Inertia::render('Profile/Security', [
            'localCredentialStatus' => $request->user()->local_credential_status,
            'identities' => $request->user()->externalIdentities()->get(['provider'])
                ->map(fn ($identity): array => ['provider' => $identity->provider])->all(),
            'providers' => collect(config('services.oauth_providers', []))
                ->filter(fn (string $provider): bool => (bool) config("services.$provider.client_id"))
                ->map(fn (string $provider): array => [
                    'name' => $provider, 'url' => route('login.oauth', $provider, false),
                ])->values()->all(),
        ]);
    }

    public function preferences(Request $request): Response
    {
        return Inertia::render('Profile/Preferences', [
            'settings' => $request->user()->settings->toArray(),
            'locales' => config('app.supported_locale'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());
        return back()->with('success', __('profile.Kontodaten gespeichert.'));
    }

    public function updatePreferences(ProfilePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $settings = json_decode($user->getRawOriginal('settings') ?? '{}', true) ?? [];
        $user->settings = array_replace($settings, $request->validated());
        $user->save();
        return back()->with('success', __('profile.Einstellungen gespeichert.'));
    }

    public function updatePassword(ProfilePasswordRequest $request, LocalPassword $passwords): RedirectResponse
    {
        $user = $request->user();
        $passwords->replace($user, $request->validated('password'));
        $passwords->keepCurrentSession($request, $user);
        return back()->with('success', __('profile.Passwort geändert. Andere Sitzungen wurden abgemeldet.'));
    }
}
