<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CredentialChallenges;
use App\Actions\Auth\LocalPassword;
use App\Http\Controllers\Controller;
use App\Models\CredentialChallenge;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class ResetPasswordController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['guest', 'throttle:10,1'];
    }

    public function showResetForm(): RedirectResponse
    {
        return redirect()->route('password.request')->with('status', __('profile.Bitte fordern Sie einen neuen Bestätigungslink an.'));
    }

    public function reset(Request $request, CredentialChallenges $challenges, LocalPassword $passwords): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'], 'token' => ['required', 'string'],
            'password' => LocalPassword::rules(), 'user_id' => ['missing'], 'is_admin' => ['missing'],
        ]);
        $userId = User::where('email', $data['email'])->value('id') ?? 0;
        $user = $challenges->consume($userId, CredentialChallenge::RESET_PASSWORD, $data['token'], $data['password']);
        $passwords->keepCurrentSession($request, $user);

        return redirect()->route('profile.security')->with('success', __('profile.Passwort eingerichtet.'));
    }
}
