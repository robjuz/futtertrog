<?php

namespace App\Http\Middleware;

use App\Models\User;
use Cknow\Money\Money;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
    public function handle(Request $request, Closure $next)
    {
        $language = App::getLocale();

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $language = $user->settings->language ?? $language;
        }


        App::setLocale($language);

        Money::setLocale($language);

        return $next($request);
    }
}
