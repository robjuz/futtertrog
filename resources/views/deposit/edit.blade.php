@extends('layouts.app')

@section('content')

    <h1>{{ __('Edit deposit') }}</h1>

    <form action="{{route('deposits.update', $deposit)}}" method="post">
        @csrf()
        @method('put')

        <label for="user_id">
            <span>{{ __('User') }}</span>
            @error('user_id')
                <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="user_id" name="user_id">
            @foreach ($users as $user)
                <option
                    value="{{ $user->id }}"
                    {{ old('user_id', $deposit->user_id) == $user->id ? 'selected' : ''}}
                >
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <label for="value">
            <span>{{ __('Value') }}</span>
            @error('value')
                <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="value" type="number" name="value" step="0.01" pattern="\d*" value="{{ $deposit->value * 0.01 }}">

        <label for="comment">
            <span>{{ __('Comment') }}</span>
            @error('comment')
                <span>{{ $message }}</span>
            @enderror
        </label>

        <textarea id="comment" name="comment" value="{{ $deposit->comment }}"></textarea>

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection()
