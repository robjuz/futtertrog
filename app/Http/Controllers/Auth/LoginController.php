<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CredentialChallenges;
use App\Http\Controllers\Controller;
use App\Models\CredentialChallenge;
use App\Models\ExternalIdentity;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as OAuthRedirect;

class LoginController extends Controller implements HasMiddleware
{
    use AuthenticatesUsers;

    protected string $redirectTo = '/';

    public static function middleware(): array
    {
        return [new Middleware('guest', except: ['logout', 'redirectToOauth', 'handleOauthCallback'])];
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $user = User::where('email', $request->string('email')->toString())->first();
        if ($user && $user->local_credential_status !== 'disabled' && Hash::check($request->input('password'), $user->password)) {
            if ($user->local_credential_status === 'legacy') {
                app(CredentialChallenges::class)->send($user, CredentialChallenge::LegacyPassword);
                $this->incrementLoginAttempts($request);
                return $this->pendingResponse($request);
            }
            Auth::login($user, $request->boolean('remember'));
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    public function handleOauthCallback(Request $request, string $provider, CredentialChallenges $challenges): JsonResponse|RedirectResponse
    {
        abort_unless(CredentialChallenges::providerAllowed($provider), 404);
        try {
            $external = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return $this->pendingResponse($request);
        }

        $subject = (string) $external->getId();
        if ($subject === '' || strlen($subject) > 255) {
            return $this->pendingResponse($request);
        }
        $identity = ExternalIdentity::where('provider', $provider)->where('subject', $subject)->first();
        if ($identity) {
            $user = $identity->user;
            if (! $user || $user->trashed() || ($request->user() && $request->user()->id !== $user->id)) {
                return $this->pendingResponse($request);
            }
            Auth::login($user, true);
            return $this->sendLoginResponse($request);
        }

        if ($request->user()) {
            $account = User::find($request->user()->id);
            if (! $account) {
                return $this->pendingResponse($request);
            }
            $challenges->send($account, CredentialChallenge::LinkIdentity, $provider, $subject);
            return $this->pendingResponse($request);
        }

        $email = $external->getEmail();
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return $this->pendingResponse($request);
        }
        $user = User::withTrashed()->whereRaw('lower(email) = ?', [Str::lower($email)])->first();
        if ($user) {
            $challenges->send($user, CredentialChallenge::LinkIdentity, $provider, $subject);
            return $this->pendingResponse($request);
        }

        try {
            $user = DB::transaction(function () use ($external, $email, $provider, $subject): User {
                $user = User::create([
                    'email' => $email,
                    'name' => Str::limit($external->getName() ?: $email, 255, ''),
                    'password' => Hash::make(bin2hex(random_bytes(32))),
                    'local_credential_status' => 'disabled',
                ]);
                $user->externalIdentities()->create(['provider' => $provider, 'subject' => $subject]);
                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->pendingResponse($request);
        }
        Auth::login($user, true);
        return $this->sendLoginResponse($request);
    }

    public function redirectToOauth(string $provider): OAuthRedirect
    {
        abort_unless(CredentialChallenges::providerAllowed($provider), 404);
        return Socialite::driver($provider)->redirect();
    }

    protected function sendLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie(Auth::user()->password));
        $this->clearLoginAttempts($request);

        return $request->wantsJson()
            ? response()->json(['authenticated' => true])
            : redirect()->intended($this->redirectTo);
    }

    private function pendingResponse(Request $request): JsonResponse|RedirectResponse
    {
        return $request->wantsJson()
            ? response()->json(['message' => CredentialChallenges::PublicMessage])
            : redirect()->route($request->user() ? 'profile.security' : 'login')
                ->with('status', CredentialChallenges::PublicMessage);
    }

    protected function loggedOut(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
