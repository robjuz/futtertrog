<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <meta name="Description" content="{{ __('futtertrog.description') }}">

    <link href="https://fonts.googleapis.com/css2?family=Caveat&family=Livvic&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <script>
        window.Futtertrog = {!!  json_encode([
            'messages' => [
                'are_you_sure' => __('Are you sure?'),
                ],
            'csrf' => csrf_token(),
            'user' => auth()->id()
        ]) !!};
    </script>

    @include('partials.pwa')

</head>
<body id="{{ Route::currentRouteName() }}">
@if (session('success'))
    <p class="success-message">
        {{ session('success') }}
    </p>
@endif

@if (session('error'))
    <p class="error-message">
        {{ session('error') }}
    </p>
@endif

@auth()
    <a class="sr-only skip-link skip-navigation"
       href="#main" <?php /* keep this link synchronised with main's id */ ?>
    >
        {{ __('Skip navigation') }}
    </a>

    @hasSection('navigation')
        @yield('navigation')
    @else
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
                <li><a href="{{ route('my.orders') }}">{{ __('My orders') }}</a></li>
                <li><a href="{{ route('my.balance') }}">{{ __('My balance') }}</a></li>
                <li>
                    <a href="{{ route('profile.account') }}#profile-content" {{ request()->routeIs('profile.*') ? 'aria-current="page"' : '' }}>
                        @svg('solid/cogs', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        {{ __('Profile') }}
                    </a>
                </li>
                @can('access-administration')
                    <li><a href="{{ route('filament.administration.pages.dashboard') }}">{{ __('Administration') }}</a></li>
                @endcan
                <li>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit">{{ __('Logout') }}</button>
                    </form>
                </li>
            </ul>
        </nav>
    @endif

@endauth

<p id="offline-status" role="alert" hidden>Sie sind offline. Bestellungen werden nicht gesendet oder vorgemerkt.</p>
<p id="action-error" role="alert" tabindex="-1" hidden></p>
@yield('before')

<main id="main" class="@stack('main-classes')" <?php /* keep this id for skip link */ ?>>
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
