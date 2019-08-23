@extends('layouts.app')

@section('content')

    @include('partials.user_menu')

    <main>
        <h1>
            {{ __('New user') }}
        </h1>

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <label for="name">
                <span>
                    {{ __('Name') }}
                </span>
                @if ($errors->has('name'))
                    <span>
                        {{ $errors->first('name') }}
                    </span>
                @endif
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>


            <label for="email">
                <span>
                    {{ __('E-Mail Address') }}
                </span>
                @if ($errors->has('email'))
                    <span>{{ $errors->first('email') }}</span>
                @endif
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="password">
                <span>
                    {{ __('Password') }}
                </span>
                @if ($errors->has('password'))
                    <span>{{ $errors->first('password') }}</span>
                @endif
            </label>
            <input id="password" type="password" name="password" required>

            <label for="password-confirm">
                <span>
                    {{ __('Confirm Password') }}
                </span>
            </label>
            <input id="password-confirm" type="password" name="password_confirmation" required>

            <input type="checkbox"
                   name="is_admin"
                   id="is_admin"
                   {{ old('is_admin') ? 'checked' : '' }}
                   value="1"
            >
            <label for="is_admin">
                {{ __('Is admin') }}
            </label>

            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
        </form>

    </main>

@endsection
