@extends('user_order.wrapper')

@section('wrapper-content')
    <form action="{{ route('order_items.store') }}" method="post">
        @csrf

        <label for="user_id">
            <span>{{__('User')}}</span>
            @error('user_id'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <select id="user_id" name="user_id">
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
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
                <option value="{{ $meal->id }}">{{ $meal->title }}</option>
            @endforeach
        </select>

        <label for="quantity">
            <span>{{__('Quantity')}}</span>
            @error('quantity'))
                <span>{{ $message }}</span>
            @enderror
        </label>
        <input id="quantity" type="number" min="1" pattern="\d*" name="quantity">

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection
