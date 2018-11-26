@extends('layouts.app')

@section('content')

    <h1>{{ __('Reset Password') }}</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">
            <span>{{ __('E-Mail Address') }}</span>
            @error('email'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="email" type="email" name="email" required autofocus
               value="{{ $email ?? old('email') }}">

        <label for="password">
            <span>{{ __('Password') }}</span>
            @error('password'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="password" type="password" name="password" required>

        <label for="password-confirm" class="col-form-label-sm">
            {{ __('Confirm Password') }}
        </label>

        <input id="password-confirm" type="password" name="password_confirmation" required>

        <button type="submit">{{ __('Reset Password') }}</button>
    </form>

@endsection
