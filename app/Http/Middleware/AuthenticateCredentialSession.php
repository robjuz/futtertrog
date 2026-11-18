<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCredentialSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::getDefaultDriver() !== 'web') {
            return $next($request);
        }

        $guard = Auth::guard('web');
        $user = $guard->user();
        /**
         * Pre-migration sessions have no password fingerprint. They may continue
         * while credentials remain legacy, but must not survive confirmed setup.
         * Every new login and successful confirmation stores its fingerprint.
         */
        if ($user?->local_credential_status === 'enabled' &&
            $request->session()->has($guard->getName()) &&
            ! $request->session()->has('password_hash_web')) {
            $guard->logoutCurrentDevice();
            $request->session()->invalidate();
            throw new AuthenticationException(redirectTo: route('login'));
        }

        return app(AuthenticateSession::class)->handle($request, $next);
    }
}
