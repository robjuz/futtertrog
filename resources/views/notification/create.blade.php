@extends('layouts.app')

@section('content')
    <h1>{{ __('New notification') }}</h1>

    <form action="{{route('notification.store')}}" method="post">
        @csrf()

        <label for="user_id">
            <span>{{ __('User') }}</span>
            @error('user_id')
                <span>{{ $message }}</span>
            @enderror
        </label>

        <select id="user_id" name="user_id[]" multiple>
            @foreach ($users as $user)
                <option
                    value="{{ $user->id }}"
                    {{ old('source') == $user->id ? 'selected' : ''}}
                >
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <label for="subject">
            <span>{{ __('Subject') }}</span>
            @error('subject')
                <span>{{ $message }}</span>
            @enderror
        </label>

        <input id="subject" name="subject">

        <label for="body">
            <span>{{ __('Message') }}</span>
            @error('body')
                <span>{{ $message }}</span>
            @enderror
        </label>

        <textarea id="body" name="body"></textarea>

        <button type="submit">{{ __('Submit') }}</button>
    </form>
@endsection()
