@extends('layouts.app')

@section('content')

    @include('partials.user_menu')

    <h1>{{ __('New notification') }}</h1>

    <form action="{{route('notification.store')}}" method="post">
        @csrf()

        <p id="user_id-desc">
            <span>{{ __('User') }}</span>
            @error('user_id')
                <span>{{ $message }}</span>
            @enderror
        </p>

        @foreach ($users as $user)
            <input type="checkbox"
                   id="user_id-{{ $user->id }}"
                   value="user_id[{{ $user->id }}]"
                   {{ old('user_id') == $user->id ? 'checked' : ''}}
                   aria-describedby="user_id-desc"
            >
            <label for="user_id-{{ $user->id }}">{{ $user->name }}</label>
        @endforeach

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
