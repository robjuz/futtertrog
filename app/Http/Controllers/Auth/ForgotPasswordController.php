<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CredentialChallenges;
use App\Http\Controllers\Controller;
use App\Models\CredentialChallenge;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class ForgotPasswordController extends Controller implements HasMiddleware
{
    use SendsPasswordResetEmails;

    public static function middleware(): array
    {
        return ['guest', 'throttle:10,1'];
    }

    public function sendResetLinkEmail(Request $request, CredentialChallenges $challenges): JsonResponse|RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            $challenges->send($user, CredentialChallenge::ResetPassword);
        }

        return $request->wantsJson()
            ? response()->json(['message' => CredentialChallenges::PublicMessage])
            : back()->with('status', CredentialChallenges::PublicMessage);
    }
}
