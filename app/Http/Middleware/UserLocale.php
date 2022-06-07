<?php

namespace App\Http\Middleware;

use App\User;
use Cknow\Money\Money;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $language = app()->getLocale();

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $language = $user->settings->language ?? $language;
        }

        app()->setLocale($language);

        Money::setLocale($language);

        return $next($request);
    }
}
