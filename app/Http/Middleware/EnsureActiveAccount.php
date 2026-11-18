<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->fresh();
        abort_if(!$user || $user->trashed(), 403);
        auth()->guard()->setUser($user);

        return $next($request);
    }
}
