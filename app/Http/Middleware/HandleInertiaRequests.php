<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'profile';

    protected $withoutSsr = ['*'];

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'translations' => __('profile'),
            'navigation' => [
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
