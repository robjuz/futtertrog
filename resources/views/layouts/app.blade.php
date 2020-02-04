<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        window.Futtertrog = @json([
            'user' => Auth::user(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'csrf' => csrf_token()
        ]);

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/serviceworker.js').then(function(reg) {
                listenForWaitingServiceWorker(reg, promptUserToRefresh);
            });

            function listenForWaitingServiceWorker(reg, callback) {
                function awaitStateChange() {
                    reg.installing.addEventListener('statechange', function() {
                        if (this.state === 'installed') callback(reg);
                    });
                }
                if (!reg) return;
                if (reg.waiting) return callback(reg);
                if (reg.installing) awaitStateChange();
                reg.addEventListener('updatefound', awaitStateChange);
            }

            function promptUserToRefresh() {
                if (window.confirm("New version available! OK to refresh?")) {
                    window.location.reload();
                }
            }

        }
    </script>
</head>
<body id="{{ Route::currentRouteName() }}">
@if (session('success'))
    <p class="success-message">
        {{ session('success') }}
    </p>
@endif

@auth()
    <a class="sr-only skip-link skip-navigation"
       href="#main" <?php /* keep this link synchronised with main's id */?>
    >
        {{ __('Skip navigation') }}
    </a>

    <nav id="main-navbar" data-button="{{ __('Menu') }}">
        <ul>
            <li>
                <a href="{{ route('home') }}#main" {{ request()->routeIs('home') ? 'aria-current="page"' : '' }}>
                    @svg('solid/home', ['aria-hidden', 'focusable="false"'])
                    {{ __('Dashboard') }}
                </a>
            </li>

            <li>
                <a href="{{ route('meals.index') }}#main" {{ request()->routeIs('meals.index') ? 'aria-current="page"' : '' }}>
                    @svg('solid/utensils', ['aria-hidden', 'focusable="false"'])
                    {{ __('Place order') }}
                </a>
            </li>
            @can('create', \App\Meal::class)
                <li>
                    <a href="{{ route('meals.create') }}#main" {{ request()->routeIs('meals.create') ? 'aria-current="page"' : '' }}>
                        @svg('solid/plus', ['aria-hidden', 'focusable="false"'])
                        {{ __('New meal') }}
                    </a>

                </li>
            @endcan
            @can('list', \App\Order::class)
                <li>
                    <a href="{{ route('orders.index') }}#main" {{ request()->routeIs('orders.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/tasks', ['aria-hidden', 'focusable="false"'])
                        {{ __('Manage orders') }}
                    </a>
                </li>
            @endcan

            @can('viewAny', \App\User::class)
                <li>
                    <a href="{{ route('users.index') }}#main" {{ request()->routeIs('users.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/users', ['aria-hidden', 'focusable="false"'])
                        {{ __('Manage users') }}
                    </a>
                </li>
            @endcan

            @can('viewAny', \App\Deposit::class)
                <li>
                    <a href="{{ route('deposits.index') }}#main" {{ request()->routeIs('deposits.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/euro-sign', ['aria-hidden', 'focusable="false"'])
                        {{ __('Manage deposits') }}
                    </a>
                </li>
            @endcan
            <li>
                <a href="{{ route('settings.index') }}#main" {{ request()->routeIs('settings.index') ? 'aria-current="page"' : ''}}>
                    @svg('solid/cogs', ['aria-hidden', 'focusable="false"'])
                    {{ __('Settings') }}
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}">
                    @svg('solid/sign-out-alt', ['aria-hidden', 'focusable="false"'])
                    {{ __('Logout') }}
                </a>
            </li>
        </ul>
    </nav>
@endauth

@yield('before')

<main id="main" class="@stack('main-classes')" <?php /* keep this id for skip link */?>>
    @yield('content')
</main>

<footer id="mainFooter">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</footer>
@stack('scripts')
<script src="{{ asset('js/app.js') }}"></script>
@auth()
window.Futtertrog.pushNotifications.enable();
@elseauth()
    window.Futtertrog.pushNotifications.disable();
@endauth
<style>
    #main-navbar {display: block;}
</style>
</body>
</html>
