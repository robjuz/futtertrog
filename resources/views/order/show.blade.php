@extends('layouts.app')

@section('content')
    <h1>{{ __('Order') }} {{ $order->date->format('d F Y') }}</h1>

    @foreach($order->meals as $meal)
        <h2>{{ $meal->title }}</h2>

        <p>{{ $meal->description }}</p>

        <h3>{{ __('Was ordered by:') }}</h3>
        <ul>
            @foreach($meal->users as $user)
                <li>{{ $user->name }}</li>
            @endforeach
        </ul>
    @endforeach
@endsection
