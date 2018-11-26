@extends('layouts.app')

@section('content')

    <header>
        <h1>{{ __('Login') }}</h1>
    </header>

    <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">
                <span>{{ __('E-Mail Address') }}</span>
                @error('email')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="email" autofocus name="email" type="email" required
                value="{{ old('email') }}">

            <label for="password">
                <span>{{ __('Password') }}</span>
                @error('password')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="password" name="password" type="password" required>

            <input id="remember" name="remember" type="checkbox"
                {{ old('remember') ? 'checked' : '' }}>
            <label for="remember">
                {{ __('Remember Me') }}
            </label>

            <button type="submit">{{ __('Login') }}</button>


            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            @endif
        </form>

    @if (config('services.gitlab.enabled'))
        <form method="GET" action="{{ route('login.gitlab') }}">
            <div class="or-login-with"><span>{{ __('or') }}</span></div>

            <button type="submit" class="login-with-gitlab">
                @svg('brands/gitlab', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                {{ __('Login with GitLab') }}
            </button>

            <input id="remember_gitlab" name="remember" type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}>
            <label for="remember_gitlab">
                {{ __('Remember Me') }}
            </label>
        </form>
    @endif
@endsection
