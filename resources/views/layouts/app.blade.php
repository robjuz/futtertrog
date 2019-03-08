<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    @laravelPWA

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" async defer></script>
</head>
<body>

<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <a class="navbar-brand text-uppercase" href="{{ url('/') }}" title="{{ config('app.name') }}">
        <h1> {{ config('app.name') }} </h1>
    </a>

    <input type="checkbox" id="nav-toggler" class="d-none"/>
    <label for="nav-toggler" class="navbar-toggler"><span class="navbar-toggler-icon"></span></label>

    <div class="collapse navbar-collapse">
		<a class="skip-link skip-navigation" href="#main" tabindex="1">
			Menü überspringen
		</a>

        <!-- Left Side Of Navbar -->
        @auth()
            <ul class="navbar-nav mr-auto">

                <li class="nav-item {{ request()->routeIs('meals.index') ? 'active' : '' }}">
                    <a href="{{ route('meals.index') }}" class="nav-link" title="{{ __('Place order') }}">
                        {{ __('Place order') }}
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('meals.create') ? 'active' : '' }}">
                    @can('create', \App\Meal::class)
                        <a href="{{ route('meals.create') }}" class="nav-link" title="{{ __('New meal') }}">
                            {{ __('New meal') }}
                        </a>

                    @endcan
                </li>
                @can('list', \App\Order::class)
                    <li class="nav-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                        <a href="{{ route('orders.index') }}" class="nav-link" title="{{ __('Manage orders') }}">
                            {{ __('Manage orders') }}
                        </a>
                    </li>
                @endcan

                @can('list', \App\User::class)
                    <li class="nav-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}" class="nav-link" title="{{ __('Manage users') }}">
                            {{ __('Manage users') }}
                        </a>
                    </li>
                @endcan
            </ul>
        @endauth

        <!-- Right Side Of Navbar -->
        <!-- Authentication Links -->
        @guest
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}" title="{{ __('Login') }}">
                        {{ __('Login') }}
                    </a>
                </li>
                @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}" title="{{ __('Register') }}">
                            {{ __('Register') }}
                        </a>
                    </li>
                @endif
            </ul>
        @else
            <div class="navbar-nav ml-auto flex-row align-items-center">
                <img src="{{ Auth::user()->gravatarUrl(50) }}" class="rounded-circle mr-3 mr-lg-1" alt="" width="50" height="50">
                <div class="d-flex flex-column text-left">
                    <a class="nav-item nav-link" href="{{ route('settings.index') }}" title="{{ __('Settings')  }}">
                        {{ __('Settings') }}
                    </a>
                    <a class="nav-item nav-link" href="{{ route('logout') }}" title="{{ __('Logout')  }}">
                        {{ __('Logout') }}
                    </a>
                </div>
            </div>
        @endguest
    </div>
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

<footer id="mainFooter" class="text-center text-white py-3">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
    @include('partials.running_dog')
</footer>
</body>
</html>
