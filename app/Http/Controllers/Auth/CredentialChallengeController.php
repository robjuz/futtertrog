<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CredentialChallenges;
use App\Actions\Auth\LocalPassword;
use App\Http\Controllers\Controller;
use App\Models\CredentialChallenge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CredentialChallengeController extends Controller
{
    public function show(Request $request, int $user, string $purpose, string $token, CredentialChallenges $challenges): View
    {
        $this->ensureAccount($request, $user);
        $challenges->find($user, $purpose, $token);

        return view('auth.credential-confirm', ['needsPassword' => $purpose !== CredentialChallenge::LinkIdentity]);
    }

    public function confirm(Request $request, int $user, string $purpose, string $token, CredentialChallenges $challenges, LocalPassword $passwords): RedirectResponse
    {
        $this->ensureAccount($request, $user);
        $challenges->find($user, $purpose, $token);
        $data = $request->validate([
            'password' => $purpose === CredentialChallenge::LinkIdentity ? ['missing'] : LocalPassword::rules(),
            'user_id' => ['missing'], 'is_admin' => ['missing'], 'id' => ['missing'],
        ]);
        $account = $challenges->consume($user, $purpose, $token, $data['password'] ?? null);
        $passwords->keepCurrentSession($request, $account);

        return redirect()->route('profile.security')->with('success', __('profile.Anmeldemethode bestätigt.'));
    }

    public function send(Request $request, CredentialChallenges $challenges): RedirectResponse
    {
        if ($request->user()->local_credential_status !== 'enabled') {
            $challenges->send($request->user(), CredentialChallenge::SetPassword);
        }

        return back()->with('success', CredentialChallenges::PublicMessage);
    }

    private function ensureAccount(Request $request, int $userId): void
    {
        if ($request->user() && $request->user()->id !== $userId) {
            throw ValidationException::withMessages(['confirmation' => CredentialChallenges::InvalidMessage]);
        }
    }
}
