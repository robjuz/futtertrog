<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class UserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()){
            $language = Auth::user()->settings[User::SETTING_LANGUAGE];
        } else {
            $language = $request->getPreferredLanguage(config('app.supported_locales'));
        }

            app()->setLocale($language);


        return $next($request);

    }
}
