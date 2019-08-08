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
    </script>
</head>
<body>

<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
    <a href="/">Home</a>

    @auth()
        <input type="checkbox" id="nav-toggler" class="d-none"/>
        <label for="nav-toggler" class="navbar-toggler"><span class="navbar-toggler-icon"></span></label>

        <div class="collapse navbar-collapse">
            <a class="skip-link skip-navigation" href="#main" tabindex="1">
                {{ __('Skip navigation') }}
            </a>

            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav mr-auto">

                <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                    <a href="{{ route('home') }}" class="nav-link">
                        {{ __('Dashboard') }}
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('meals.index') ? 'active' : '' }}">
                    <a href="{{ route('meals.index') }}" class="nav-link">
                        {{ __('Place order') }}
                    </a>
                </li>
                @can('create', \App\Meal::class)
                    <li class="nav-item {{ request()->routeIs('meals.create') ? 'active' : '' }}">
                        <a href="{{ route('meals.create') }}" class="nav-link">
                            {{ __('New meal') }}
                        </a>

                    </li>
                @endcan
                @can('list', \App\Order::class)
                    <li class="nav-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                        <a href="{{ route('orders.index') }}" class="nav-link">
                            {{ __('Manage orders') }}
                        </a>
                    </li>
                @endcan

                @can('viewAny', \App\User::class)
                    <li class="nav-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}" class="nav-link">
                            {{ __('Manage users') }}
                        </a>
                    </li>
                @endcan
            </ul>
            <div class="navbar-nav ml-auto flex-row align-items-center">
                <img src="{{ Auth::user()->gravatarUrl(50) }}"
                     class="rounded-circle mr-3 mr-lg-1"
                     alt=""
                     width="50"
                     height="50"
                >
                <div class="d-flex flex-column text-left">
                    <a class="nav-item nav-link" href="{{ route('settings.index') }}">
                        {{ __('Settings') }}
                    </a>
                    <a class="nav-item nav-link" href="{{ route('logout') }}">
                        {{ __('Logout') }}
                    </a>
                </div>
            </div>
        </div>
    @endauth
</nav>

@if (session('success'))
    <p>
        {{ session('success') }}
    </p>
@endif

@yield('content')

<footer id="mainFooter">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</footer>

<script src="{{ asset('js/app.js') }}" async defer></script>
</body>
</html>
