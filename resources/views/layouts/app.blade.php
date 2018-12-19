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

    <!-- MYSZA STYLES -->
    <style>

        .btn {
            border-radius: 0 !important;
        }

        .alert-danger,
        .alert-success,
        .alert-warning {
            background-color: #fff;
            border-radius: 0;
            border-width: 0;
            border-left-width: 3px;
            margin-top: 1em;
        }

        h2 {
            color: #00345E;
        }

        /**** HEADER ****/
        .sticky-top {
            background-image: linear-gradient(to bottom right, #0186f5, #1E98FE);
            border-bottom: 3px solid #01569E;
            box-shadow: 0 0 10px 5px #ccc;
        }

        .navbar-brand {
            display: inline-block;
            font-size: 2em;
            outline: none !important;
            transition: 0.3s;
        }

        .navbar-dark .navbar-brand:hover {
            color: #01569E;
        }

        .navbar-brand::first-letter {
            font-size: 1.4em;
        }

        /**** CARD ****/
        .card {
            border-radius: 0;
            box-shadow: 0 0 10px 5px #eee;
        }

        .card .card {
            border: none;
            box-shadow: none;
        }

        .card-header {
            background-color: #f7f7f7;
            border-bottom: 2px solid #3C86C4;
            color: #00345E;
            font-size: 17px;
        }

        .card.bg-danger {
            background: transparent !important;
            border: none;
            border-left: 3px solid #dc3545;
        }

        /**** LOGIN FORM ****/

        /** CHECKBOX **/
        /* Customize the label (the container) */
        .form-check {
            display: block;
            position: relative;
            padding-left: 35px;
            margin-bottom: 12px;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Hide the browser's default checkbox */
        .form-check input {
            left: 0;
            margin: 0;
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 25px;
            width: 25px;
            z-index: 1;
        }

        /* Create a custom checkbox */
        .checkmark {
            border: 1px solid #ced4da;
            position: absolute;
            top: 0;
            left: 0;
            height: 25px;
            width: 25px;
            background-color: #eee;
        }

        /* Align label vertically */
        .checkmark ~ .form-check-label {
            line-height: 25px;
            vertical-align: middle;
        }

        /* On mouse-over, add a grey background color */
        .form-check:hover input ~ .checkmark {
            background-color: #ccc;
        }

        /* When the checkbox is checked, add a blue background */
        .form-check input:checked ~ .checkmark {
            background-color: #1E98FE;
        }

        /* Create the checkmark/indicator (hidden when not checked) */
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        /* Show the checkmark when checked */
        .form-check input:checked ~ .checkmark:after {
            display: block;
        }

        /* Style the checkmark/indicator */
        .form-check .checkmark:after {
            left: 8px;
            top: 3px;
            width: 8px;
            height: 15px;
            border: solid white;
            border-width: 0 3px 3px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg);
        }

        /** OTHER **/
        .col-form-label {
            font-size: 0.85em;
        }

        /**** CALENDAR ****/
        .vdp-datepicker__calendar header > span {
            background: #01569E;
            color: #fff;
        }

        .vdp-datepicker__calendar header > span:hover {
            background-color: #00345E !important;
        }

        .vdp-datepicker__calendar .cell.day-header {
            color: #3C86C4;
        }

    </style>
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
