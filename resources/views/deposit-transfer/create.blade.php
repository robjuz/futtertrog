@extends('layouts.app')

@section('content')

    @include('partials.user_menu')

    <h1>{{ __('New money transfer') }}</h1>

    <form action="{{ route('deposits.transfer') }}" method="post">
        @csrf()

        <label for="source">
            <span>{{ __('From user') }}</span>
            @error('source')
                <span>{{ $message }}</span>
            @enderror
        </label>

        <select id="source" name="source">
            @foreach ($users as $user)
                <option
                    value="{{ $user->id }}"
                    {{ old('source') == $user->id ? 'selected' : ''}}
                >
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <label for="target">
            <span>{{ __('To user') }}</span>
            @error('target'))
                <span>{{ $message }}</span>
            @enderror
        </label>

        <select id="target" name="target">
            @foreach ($users as $user)
                <option
                    value="{{ $user->id }}"
                    {{ old('target') == $user->id ? 'selected' : ''}}
                >
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <label for="value">
            <span>{{ __('Value') }}</span>
            @error('value'))
                <span>{{ $message }}</span>
            @enderror
        </label>

        <input type="number" name="value" id="value" step="any" pattern="\d*"
            value="{{ old('value') }}">

        <label for="comment">
            <span>
                {{ __('Comment') }}
            </span>
            @error('comment'))
                <span>{{ $errors->first('comment') }}</span>
            @enderror
        </label>
        <textarea name="comment" id="comment"></textarea>

        <button type="submit">{{ __('Create') }}</button>
    </form>
@endsection()
