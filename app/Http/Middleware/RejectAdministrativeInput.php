<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectAdministrativeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'user_id' => ['missing'],
            'is_admin' => ['missing'],
            'order_id' => ['missing'],
            'status' => ['missing'],
            'payed_at' => ['missing'],
        ]);

        return $next($request);
    }
}
