<?php

namespace App\Filament\Middleware;

use App\Http\Middleware\EnsureActiveAccount;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelAccess implements AuthenticatesRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        return app(EnsureActiveAccount::class)->handle($request, function (Request $request) use ($next): Response {
            Gate::authorize('access-administration');

            return $next($request);
        });
    }
}
