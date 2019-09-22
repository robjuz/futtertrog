@extends('layouts.app')

@section('content')
    <h1>{{ $order->date->format('d F Y') }}</h1>

    @if ($order->status === \App\Order::STATUS_OPEN)
        @can('update', $order)
            <form class="collapsible" action="{{ route('orders.update', $order) }}" method="POST">
                @method('put')
                @csrf
                <input type="hidden" name="status" value="{{ \App\Order::STATUS_ORDERED }}">
                <button type="submit">
                    {{ __('Mark as ordered') }}
                </button>
            </form>
        @endcan
    @endif

    @foreach($order->orderItemsCompact() as $orderItem)
        <h2>{{ $orderItem->meal->title }}</h2>

        <p>{{ $orderItem->meal->description }}</p>

        <h3>{{ __('Was ordered by:') }}</h3>
        <ul>
            @foreach($orderItem->users as $user)
                <li>
                    <a href="{{ route('users.show', $user) }}">
                        {{ $user->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach
@endsection
