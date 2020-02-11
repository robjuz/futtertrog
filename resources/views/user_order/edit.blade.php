@extends('layouts.app')

@section('content')
    <h1>
        <span>{{ __('Edit order') }}</span>
        {{ $orderItem->order->date->format(__('futtertrog.date_format')) }}
    </h1>

    <form action="{{ route('order_items.update', $orderItem) }}" method="post">
        @csrf
        @method('put')

        <label for="user_id">
            <span>{{__('User')}}</span>
            @error('user_id'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="user_id" name="user_id">
            @foreach($users as $user)
                <option
                    value="{{ $user->id }}"
                    {{ old('user_id', $orderItem->user_id) === $user->id ? 'selected' : '' }}
                >
                    {{ $user->name }}
                </option>
            @endforeach
        </select>

        <label for="meal_id">
            <span>{{__('Meal')}}</span>
            @error('meal_id'))
                <span>{{ $message }}</span>
            @enderror

        </label>
        <select id="meal_id" name="meal_id">
            @foreach($meals as $meal)
                <option
                    value="{{ $meal->id }}"
                    {{ old('user_id', $orderItem->meal_id) === $meal->id ? 'selected' : '' }}
                >
                    {{ $meal->title }}
                </option>
            @endforeach
        </select>

        <label for="quantity">
            <span>{{__('Quantity')}}</span>
            @error('quantity'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="quantity" type="number" min="1" pattern="\d*" name="quantity" value="{{ old('quantity', $orderItem->quantity) }}">

        <input type="hidden" name="date" value="{{ $orderItem->date }}"/>

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection
