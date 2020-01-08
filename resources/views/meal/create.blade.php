@extends('layouts.app')

@section('content')
    <h1>{{ __('New meal') }}</h1>

    <form action="{{ route('meals.store') }}" method="post">
        @csrf

        <label for="provider">
            <span>{{ __('Provider') }}</span>
            @error('provider')
            <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="provider" name="provider">
            <option value="">---</option>
            @foreach(\App\Meal::$providers as $provider)
                <option value="{{ $provider }}">{{ $provider }}</option>
            @endforeach
        </select>

        <label for="title">
            <span>{{__('Title')}}</span>
            @error('title')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <input type="text" name="title" id="title" required
               value="{{ old('title') }}">

        <label for="price">
            <span>{{ __('Price') }} (in €)</span>
            @error('price')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <input id="price" type="number" name="price" pattern="\d*" required min="0" step="0.01"
               value="{{ old('price') }}">

        <label for="date_from">
            <span>{{ __('From') }}</span>
            @error('date_from')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <input id="date_from" type="date" name="date_from" required
               value="{{ old('date_from') }}">

        <label for="date_to">
            <span>{{ __('To') }}</span>
            @error('date_to')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <input id="date_to" type="date" name="date_to" required
               value="{{ old('date_to') }}">

        <label for="description">
            <span>{{ __('Description') }}</span>
            @error('description')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <textarea id="description" name="description">{{ old('description') }}</textarea>

        <input id="notify" name="notify" type="checkbox"
            {{ old('notify') ? 'checked' : '' }}>

        <label for="notify">
            {{ __('Notify users') }}
        </label>

        <button type="submit">{{ __('Create') }}</button>
    </form>

    <form action="{{ route('meals.import') }}" method="post">
        @csrf

        <label for="import_provider">
            <span>{{ __('Provider') }}</span>
            @error('provider')
            <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="import_provider" name="provider" required>
            @foreach(\App\Meal::$providers as $provider)
                <option value="{{ $provider }}">{{ $provider }}</option>
            @endforeach
        </select>

        <label for="date">
            <span>{{ __('Date') }}</span>
            @error('date')
            <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="date" type="date" name="date" required value="{{ old('date') }}">

        <input id="import_notify" name="notify" type="checkbox"
            {{ old('notify') ? 'checked' : '' }}>

        <label for="import_notify">
            {{ __('Notify users') }}
        </label>

        <button type="submit">{{ __('Create') }}</button>
    </form>
@endsection()
