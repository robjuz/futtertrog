<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    {{--@laravelPWA--}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        window.Futtertrog = @json([
            'user' => Auth::user(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'csrf' => csrf_token()
        ]);
    </script>
    <link href="https://fonts.googleapis.com/css?family=Caveat|Livvic&display=swap" rel="stylesheet">
</head>
<body>
@auth()
    <a class="sr-only skip-link skip-navigation" href="#main">
        {{ __('Skip navigation') }}
    </a>
@endauth()

<a href="/">Home</a>

@auth()

<nav id="main-navbar">
    <button
            aria-haspopup="true"
            aria-controls="main-menu">
        <span class="sr-only">Menü</span>
        <span aria-hidden="true">&larr;</span>
    </button>
    <ul id="main-menu">
        <li>
            <a href="{{ route('home') }}" {{ request()->routeIs('home') ? 'aria-current="page"' : '' }}>
                {{ __('Dashboard') }}
            </a>
        </li>

        <li>
            <a href="{{ route('meals.index') }}" {{ request()->routeIs('meals.index') ? 'aria-current="page"' : '' }}>
                {{ __('Place order') }}
            </a>
        </li>
        @can('create', \App\Meal::class)
            <li>
                <a href="{{ route('meals.create') }}" {{ request()->routeIs('meals.create') ? 'aria-current="page"' : '' }}>
                    {{ __('New meal') }}
                </a>

            </li>
        @endcan
        @can('list', \App\Order::class)
            <li>
                <a href="{{ route('orders.index') }}" {{ request()->routeIs('orders.index') ? 'aria-current="page"' : '' }}>
                    {{ __('Manage orders') }}
                </a>
            </li>
        @endcan

        @can('viewAny', \App\User::class)
            <li>
                <a href="{{ route('users.index') }}" {{ request()->routeIs('users.index') ? 'aria-current="page"' : '' }}>
                    {{ __('Manage users') }}
                </a>
            </li>
        @endcan
        <li>
            <a href="{{ route('settings.index') }}" {{ request()->routeIs('settings.index') ? 'aria-current="page"' : ''}}>
                {{ __('Settings') }}
            </a>
        </li>
        <li>
            <a href="{{ route('logout') }}">
                {{ __('Logout') }}
            </a>
        </li>
    </ul>
</nav>
@endauth

@if (session('success'))
    <p>
        {{ session('success') }}
    </p>
@endif

@yield('content')

<footer id="mainFooter">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</footer>
<!--<script src="https://cdn.jsdelivr.net/npm/vue"></script>-->
<script src="{{ asset('js/app.js') }}" async defer></script>
</body>
</html>
