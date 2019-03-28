<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA
    @if ((auth()->user()->settings[\App\User::SETTING_DARK_MODE] ?? false))
        <link rel="stylesheet" href="{{ mix('css/dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ mix('css/light.css') }}">
    @endif

    <script>
        window.Futtertrog = {!! json_encode([
            'user' => Auth::user(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'csrf' => csrf_token()
        ]) !!};
    </script>
</head>
<body>

<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
    <div class="navbar-brand text-white">
        <svg class="fa-2x fa-fw" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M496 96h-64l-7.16-14.31A32 32 0 0 0 396.22 64H342.6l-27.28-27.28C305.23 26.64 288 33.78 288 48.03v149.84l128 45.71V208h32c35.35 0 64-28.65 64-64v-32c0-8.84-7.16-16-16-16zm-112 48c-8.84 0-16-7.16-16-16s7.16-16 16-16 16 7.16 16 16-7.16 16-16 16zM96 224c-17.64 0-32-14.36-32-32 0-17.67-14.33-32-32-32S0 174.33 0 192c0 41.66 26.83 76.85 64 90.1V496c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V384h160v112c0 8.84 7.16 16 16 16h64c8.84 0 16-7.16 16-16V277.55L266.05 224H96z"/></svg>
        <svg class="fa-fw" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M598.88 244.56c25.2-12.6 41.12-38.36 41.12-66.53v-7.64C640 129.3 606.7 96 565.61 96c-32.02 0-60.44 20.49-70.57 50.86-7.68 23.03-11.6 45.14-38.11 45.14H183.06c-27.38 0-31.58-25.54-38.11-45.14C134.83 116.49 106.4 96 74.39 96 33.3 96 0 129.3 0 170.39v7.64c0 28.17 15.92 53.93 41.12 66.53 9.43 4.71 9.43 18.17 0 22.88C15.92 280.04 0 305.8 0 333.97v7.64C0 382.7 33.3 416 74.38 416c32.02 0 60.44-20.49 70.57-50.86 7.68-23.03 11.6-45.14 38.11-45.14h273.87c27.38 0 31.58 25.54 38.11 45.14C505.17 395.51 533.6 416 565.61 416c41.08 0 74.38-33.3 74.38-74.39v-7.64c0-28.18-15.92-53.93-41.12-66.53-9.42-4.71-9.42-18.17.01-22.88z"/></svg>
    </div>
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

                @can('list', \App\User::class)
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

<main id="main">

    @if (session('success'))
        <div class="container">
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @yield('content')

</main>

<footer id="mainFooter" class="text-center text-white py-3 shadow-sm">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')

    <link rel="stylesheet" href="{{ asset('css/flatpickr.css') }}" as="style">
    <script src="{{ mix('js/app.js') }}" async defer></script>
</footer>
</body>
</html>
