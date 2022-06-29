@extends('layouts.app')

@section('content')

    <h1>{{ __('Edit Meal') }}</h1>

    <form action="{{ route('meals.update', $meal) }}" method="post">
        @method('put')
        @csrf

        <label for="title">
            <span>{{__('Title')}}</span>
            @if ($errors->has('title'))
                <span>{{ $errors->first('title') }}</span>
            @endif
        </label>

        <input id="title" name="title" type="text" required
            value="{{ old('title', $meal->title) }}">

        <label for="price">
            <span>{{ __('Price') }} (in €)</span>
            @if ($errors->has('price'))
                <span>{{ $errors->first('price') }}</span>
            @endif
        </label>

        <input type="number" name="price" id="price" pattern="\d*" required min="0" step="0.01"
               value="{{ old('price', money_parse($meal->price)->formatByDecimal()) }}">

        <label for="date">
            <span>{{ __('Date') }}</span>
            @if ($errors->has('date'))
                <span>{{ $errors->first('date') }}</span>
            @endif
        </label>

        <input type="date" id="date" name="date" required
               value="{{ old('date', $meal->date->toDateString()) }}">

        <label for="description">
            <span>{{ __('Description') }}</span>
            @if ($errors->has('description'))
                <span>{{ $errors->first('description') }}</span>
            @endif
        </label>

        <textarea name="description" id="description" rows="5">{{ old('description', $meal->description) }}</textarea>

        <button type="submit">{{ __('Save') }}</button>
    </form>

    <form action="{{ route('meals.destroy', $meal) }}" method="POST">
        @method('delete')
        @csrf

        <button type="submit">
            @svg('solid/trash', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
            {{  __('Delete') }}
        </button>
    </form>
@endsection()
