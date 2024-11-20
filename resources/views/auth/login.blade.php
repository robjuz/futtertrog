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


    @if (config('services.gitlab.client_id') or config('services.authentic.client_id'))
        <div class="or-login-with"><span>{{ __('or') }}</span></div>

            @if (config('services.gitlab.client_id'))
                <a href="{{ route('login.oauth', 'gitlab') }}" class="login-with-oauth">
                    @svg('brands/gitlab', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Login with GitLab') }}
                </a>
            @endif

            @if (config('services.gitlab.client_id'))
                <a href="{{ route('login.oauth', 'authentik') }}" class="login-with-oauth">
                    @svg('solid/key', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Login with Authentik') }}
                </a>
            @endif
    @endif
@endsection
