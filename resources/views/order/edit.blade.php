@extends('layouts.app')

@section('content')
    <h1>{{ $order->date->format('d F Y') }}</h1>

    <header>

    @can('update', $order)
        <form action="{{ route('orders.update', $order) }}" method="POST">
            @method('put')
            @csrf
            <input type="hidden" name="status"
                   value="{{ $order->status === \App\Order::STATUS_ORDERED ? \App\Order::STATUS_OPEN : \App\Order::STATUS_ORDERED }}">
            <button type="submit">
                {{ $order->status === \App\Order::STATUS_ORDERED ? __('Mark as open') : __('Mark as ordered') }}
            </button>
        </form>
    @endcan

    @can('delete', $order)
        <form action="{{ route('orders.update', $order) }}" method="POST">
            @method('delete')
            @csrf
            <button type="submit">
                @svg('solid/trash')
                {{  __('Delete orders') }}
            </button>
        </form>
    @endcan
    </header>

    <ol>
        @foreach($order->orderItemsCompact() as $orderItem)
            <li>
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
            </li>
        @endforeach
    </ol>
@endsection
