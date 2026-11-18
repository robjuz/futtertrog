<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'profile';

    protected $withoutSsr = ['*'];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('user-interface.blade') && $request->header('X-Inertia')
            && $request->isMethod('GET') && !$request->routeIs('profile.*')) {
            return Inertia::location($request->fullUrl());
        }

        return parent::handle($request, $next);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'translations' => __('profile'),
            'navigation' => [
                'inertia' => !config('user-interface.blade'),
                'meals' => route('meals.index', absolute: false),
                'profile' => route('profile.account', absolute: false),
                'home' => route('home', absolute: false),
                'orders' => route('my.orders', absolute: false),
                'balance' => route('my.balance', absolute: false),
                'admin' => $request->user()?->can('access-administration') ? route('admin.index', absolute: false) : null,
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'status' => fn (): ?string => $request->session()->get('status'),
            ],
        ];
    }
}
