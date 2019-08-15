@extends('layouts.app')

@section('content')
    <main>
        <header>
            <h1>{{ __('Login') }}</h1>
        </header>

        <form class="login-form" method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">
                <span>{{ __('E-Mail Address') }}</span>

                @error('email')
                <span>{{ $message }}</span>
                @enderror
            </label>
            <input
                id="email"
                autofocus
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
            />

            <label for="password">
                <span>{{ __('Password') }}</span>

                @error('password')
                <span>{{ $message }}</span>
                @enderror
            </label>
            <input
                id="password"
                name="password"
                type="password"
                required
            />

            <input
                id="remember"
                name="remember"
                type="checkbox"
                {{ old('remember') ? 'checked' : '' }}
            />
            <label for="remember">
                {{ __('Remember Me') }}
            </label>

            <button type="submit">{{ __('Login') }}</button>
        </form>

        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">
                {{ __('Forgot Your Password?') }}
            </a>
        @endif

    </main>
@endsection
