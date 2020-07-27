<?php

namespace App\Http\Middleware;

use Closure;

class EnableLoginWithGitlab
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (config('services.gitlab.enabled')) {
            return $next($request);
        }

        abort(404);
    }
}
