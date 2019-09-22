@extends('layouts.app')

@section('content')

    <h1>{{ __('Register') }}</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

            <label for="name">
                <span>{{ __('Name') }}</span>
                @error('name'))
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="name" type="text" name="name" required autofocus
                value="{{ old('name') }}">

            <label for="email">
                <span>{{ __('E-Mail Address') }}</span>
                @error('email'))
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="email" type="email" name="email" required
                   value="{{ old('email') }}">

            <label for="password">
                <span>{{ __('Password') }}</span>
                @error('password'))
                    <span>{{ $message }}</span>
                @enderror</label>
            <input id="password" type="password" name="password" required>

            <label for="password-confirm">{{ __('Confirm Password') }}</label>
            <input id="password-confirm" type="password" name="password_confirmation" required>

        <button type="submit">{{ __('Register') }}</button>
    </form>
@endsection
