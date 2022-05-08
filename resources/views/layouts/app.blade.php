<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA

    <link href="https://fonts.googleapis.com/css2?family=Caveat&family=Livvic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        window.Futtertrog = {!!  json_encode([
            'messages' => [
                'are_you_sure' => __('Are you sure?'),
]           ,
            'csrf' => csrf_token(),
            'user' => auth()->id()
        ]) !!};

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/serviceworker.js').then(function (reg) {
                listenForWaitingServiceWorker(reg, promptUserToRefresh);
            });

            function listenForWaitingServiceWorker(reg, callback) {
                function awaitStateChange() {
                    reg.installing.addEventListener('statechange', function () {
                        if (this.state === 'installed') callback(reg);
                    });
                }

                if (!reg) return;
                if (reg.waiting) return callback(reg);
                if (reg.installing) awaitStateChange();
                reg.addEventListener('updatefound', awaitStateChange);
            }

            function promptUserToRefresh() {
                document.getElementById('newVersionDialog').style.setProperty('display', 'block');
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
                    @svg('solid/home', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Dashboard') }}
                </a>
            </li>

            <li>
                <a href="{{ route('meals.index') }}#main" {{ request()->routeIs('meals.index') ? 'aria-current="page"' : '' }}>
                    @svg('solid/utensils', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Place order') }}
                </a>
            </li>
            @can('create', \App\Meal::class)
                <li>
                    <a href="{{ route('meals.create') }}#main" {{ request()->routeIs('meals.create') ? 'aria-current="page"' : '' }}>
                        @svg('solid/plus', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        {{ __('New meal') }}
                    </a>

                </li>
            @endcan
            @can('list', \App\Order::class)
                <li>
                    <a href="{{ route('orders.index') }}#main" {{ request()->routeIs('orders.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/tasks', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        {{ __('Manage orders') }}
                    </a>
                </li>
            @endcan

            @can('viewAny', \App\User::class)
                <li>
                    <a href="{{ route('users.index') }}#main" {{ request()->routeIs('users.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/users', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        {{ __('Manage users') }}
                    </a>
                </li>
            @endcan

            @can('viewAny', \App\Deposit::class)
                <li>
                    <a href="{{ route('deposits.index') }}#main" {{ request()->routeIs('deposits.index') ? 'aria-current="page"' : '' }}>
                        @svg('solid/euro-sign', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        {{ __('Manage deposits') }}
                    </a>
                </li>
            @endcan
            <li>
                <a href="{{ route('settings.index') }}#main" {{ request()->routeIs('settings.index') ? 'aria-current="page"' : ''}}>
                    @svg('solid/cogs', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Settings') }}
                </a>
            </li>
            <li>
                <a href="{{ route('logout') }}">
                    @svg('solid/sign-out-alt', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
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

<div id="newVersionDialog">
    <p>{{ __('There is a new version available. Please reload the page to see the changes.') }}</p>

    <button onclick="window.location.reload()">
        {{ __('Reload') }}
    </button>
</div>
@stack('scripts')
<script src="{{ asset('js/app.js') }}"></script>
<style>
    #main-navbar {
        display: block;
    }

    #newVersionDialog {
        display: none;
    }
</style>
</body>
</html>
