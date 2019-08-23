@extends('layouts.app')

@section('content')


    @include('partials.user_menu')

    <main>
        <h1>
            {{ __('New deposit') }}
        </h1>

        <form action="{{route('deposits.store')}}" method="post">
            @csrf()

            <label for="user_id">
                <span>
                   {{ __('User') }}
                </span>
                @error('user_id')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <select id="user_id" name="user_id">
                @foreach ($users as $user)
                    <option
                        value="{{ $user->id }}"
                        {{ old('user_id') == $user->id ? 'selected' : ''}}
                    >
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

            <label for="value">
                <span>
                    {{ __('Value') }}
                </span>
                @error('value')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input
                id="value"
                type="number"
                name="value"
                step="0.01"
                pattern="\d*"
            >

            <label for="comment">
                <span>
                    {{ __('Comment') }}
                </span>
                @error('comment')
                    <span>{{ $message }}</span>
                @enderror
            </label>

            <textarea id="comment" name="comment"></textarea>

            <button type="submit">{{ __('Create') }}</button>
        </form>
    </main>

@endsection()
