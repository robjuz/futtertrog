@extends('user_order.wrapper')

@section('wrapper-content')
    <form action="{{ route('order_items.create') }}" method="get">

        <label for="date">{{__('Date')}}</label>
        <input type="date" name="date" id="date" required>

        <button type="submit">{{ __('Next') }}</button>
    </form>
@endsection
