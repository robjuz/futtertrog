@extends('layouts.admin')

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

    <p>
        <a href="{{ \App\Filament\Resources\Meals\MealResource::getUrl(parameters: ['action' => 'import']) }}">Gerichte im Verwaltungs-Panel importieren</a>
    </p>
@endsection()
