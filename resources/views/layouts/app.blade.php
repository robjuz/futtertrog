<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div id="app" v-cloak>
    <nav class="navbar navbar-expand-md navbar-dark bg-primary sticky-top flex-column">
        <div class="container flex-wrap">
            <a class="navbar-brand text-uppercase" href="{{ url('/') }}" title="{{ config('app.name') }}">
               {{ config('app.name') }}
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <running-dog class="w-100 order-1 bg-transparent text-white"></running-dog>

            <div class="collapse navbar-collapse order-1 order-md-0" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">
                    @auth()
                        <li class="nav-item {{ request()->routeIs('meals.index') ? 'active' : '' }}">
                            <a href="{{ route('meals.index') }}" class="nav-link">
                                Essen Bestellen
                            </a>
                        </li>
                    @endauth
                    <li class="nav-item {{ request()->routeIs('meals.create') ? 'active' : '' }}">
                        @can('create', \App\Meal::class)
                            <a href="{{ route('meals.create') }}" class="nav-link">
                                Essen Anlegen
                            </a>

                        @endcan
                    </li>
                    @can('list', \App\Order::class)
                        <li class="nav-item {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('orders.index') }}">
                                Bestellungen verwalten
                            </a>
                        </li>
                    @endcan

                    @can('list', \App\User::class)
                        <li class="nav-item {{ request()->routeIs('users.index') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="nav-link" title="Essen Bestellen">
                                Benutzer verwalten
                            </a>
                        </li>
                    @endcan

                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}" title="Einloggen">
                                {{ __('Login') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            @if (Route::has('register'))
                                <a class="nav-link" href="{{ route('register') }}" title="Registrieren">
                                    {{ __('Register') }}
                                </a>
                            @endif
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-3">

        @if($errors->count())
            <div class="container">
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)
                        <div class="my-3">
                            <strong>{{ $error }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- Scripts -->
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}"></script>
<script>
  window.user = {!! json_encode(auth()->user()) !!};
</script>
</body>
</html>
