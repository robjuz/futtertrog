@extends('layouts.app')

@section('content')

    <h1>{{ __('Reset Password') }}</h1>

    @if (session('status'))
        {{ session('status') }}
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

            <label for="email">
                <span>{{ __('E-Mail Address') }}</span>
                @error('email'))
                    <span>{{ $message }}</span>
                @enderror
            </label>

            <input id="email" type="email" name="email" required
                value="{{ old('email') }}">

        <button type="submit">{{ __('Send Password Reset Link') }}</button>
    </form>

@endsection
