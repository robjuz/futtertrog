@extends('layouts.app')

@section('content')

    <header>
        <h1>{{ __('Login') }}</h1>
    </header>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

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


    @foreach (config('services.oauth_providers', []) as $provider)
        @if (config("services.$provider.client_id"))
            <a href="{{ route('login.oauth', $provider) }}" class="login-with-oauth">
                {{ __('Login') }} · {{ ucfirst($provider) }}
            </a>
        @endif
    @endforeach
@endsection
