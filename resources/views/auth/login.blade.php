@extends('layouts.app')

@section('content')

    <header>
        <h1>{{ __('Login') }}</h1>
    </header>

    <div class="login-form">
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

        @if (Route::has('login.gitlab'))
            <form action="{{ route('login.gitlab') }}">
                <button type="submit">{{ __('Login with GitLab') }}</button>
            </form>
        @endif
    </div>
@endsection
