<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    {{--<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">--}}
    {{--<link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">--}}

    <!-- Styles -->
    <link href="{{ mix('css/all.css') }}" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark bg-primary flex-column">
    <div class="container flex-wrap">
        <a class="navbar-brand text-uppercase" href="{{ url('/') }}" title="{{ config('app.name') }}">
            {{ config('app.name') }}
        </a>

        <input type="checkbox" id="nav-toggler" class="d-none"/>
        <label for="nav-toggler" class="navbar-toggler"><span class="navbar-toggler-icon"></span></label>

        @if(auth()->guest() OR auth()->user()->settings['show_dog'] ?? true)
            @include('running_dog')
        @endif


        <div class="collapse navbar-collapse order-1 order-md-0">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav mr-auto">
                @auth()
                    <li class="nav-item {{ request()->routeIs('meals.index') ? 'active' : '' }}">
                        <a href="{{ route('meals.index') }}" class="nav-link" title="{{ __('Place order') }}">
                            {{ __('Place order') }}
                        </a>
                    </li>
                @endauth
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

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ml-auto">
                <!-- Authentication Links -->
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}" title="{{ __('Login') }}">
                            {{ __('Login') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        @if (Route::has('register'))
                            <a class="nav-link" href="{{ route('register') }}" title="{{ __('Register') }}">
                                {{ __('Register') }}
                            </a>
                        @endif
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('settings.index') }}" title="{{ __('Settings')  }}">
                            {{ __('Settings') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('logout') }}" title="{{ __('Logout')  }}">
                            {{ __('Logout') }}
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<main class="py-3">

    @yield('content')

</main>

<footer class="text-center py-3">
    © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
</footer>

<script src="{{ mix('js/app.js') }}"></script>
</body>
</html>
