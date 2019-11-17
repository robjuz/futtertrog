@extends('layouts.app')

@section('content')
    <h1>{{ __('Edit user', ['name' => $user->name]) }}</h1>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @method('put')
        @csrf

        <label for="name">
            <span>{{ __('Name') }}</span>
            @if ($errors->has('name'))
                <span>{{ $errors->first('name') }}</span>
            @endif
        </label>

        <input id="name" type="text" name="name" required autofocus
               value="{{ old('name', $user->name) }}">

        <label for="email">
            <span>{{ __('E-Mail Address') }}</span>
            @if ($errors->has('email'))
                <span>{{ $errors->first('email') }}</span>
            @endif
        </label>

        <input id="email" type="email" name="email" required
               value="{{ old('email', $user->email) }}">

        <label for="password">
            <span>{{ __('Password') }}</span>
            @if ($errors->has('password'))
                <span>{{ $errors->first('password') }}</span>
            @endif
        </label>
        <input id="password" type="password" name="password">

        <label for="password-confirm">{{ __('Confirm Password') }}</label>
        <input id="password-confirm" type="password" name="password_confirmation">

        @if($user->id !== auth()->id())

            <input type="checkbox" name="is_admin" id="is_admin" value="1"
                   {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
            <label for="is_admin">{{ __('Is admin') }}</label>
        @endif

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection
