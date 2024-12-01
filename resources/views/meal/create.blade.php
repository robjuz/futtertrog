@inject('providers', 'mealProviders')
@extends('layouts.app')

@section('content')
    <h1>{{ __('New meal') }}</h1>

    <form action="{{ route('meals.store') }}" method="post">
        <h2>{{ __('Create manually') }}</h2>
        @csrf

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

        <label for="date">
            <span>{{ __('Date') }}</span>
            @error('date')
            <span>{{ $message }}</span>
            @enderror
        </label>

        <input id="date" type="date" name="date" required
               value="{{ old('date') }}">

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
        <h2>{{ __('Import meals') }}</h2>
        @csrf

        <label for="import_provider">
            <span>{{ __('Provider') }}</span>
            @error('provider')
            <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="import_provider" name="provider" required>
            @foreach($providers as $provider => $name)
                <option value="{{ $provider }}">{{ $name }}</option>
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
