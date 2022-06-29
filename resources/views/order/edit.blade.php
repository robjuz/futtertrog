@extends('layouts.app')

@section('content')
    <h1>
        @if($order->provider)
            <span class="order__provider">
                {{ $order->provider->getName() }}
            </span>
        @endif

        <span>{{ $order->getFormattedDate() }}</span>

        <span>{{ __('futtertrog.status.' . $order->status) }}</span>
    </h1>


    <header>
        @can('create', \App\OrderItem::class)
            <a href="{{ route('order_items.create') }}">
                @svg('solid/plus', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                {{ __('Create order') }}
            </a>
        @endcan

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

                <form action="{{ route('orders.update', $order) }}" method="POST">
                    @method('put')
                    @csrf
                    <input type="hidden" name="status"
                           value="{{ $order->status === \App\Order::STATUS_ORDERED ? \App\Order::STATUS_OPEN : \App\Order::STATUS_ORDERED }}">

                    <label>
                        <span>{{ __('Payed at') }}</span>
                        @error('payed_at')
                        <span>{{ $message }}</span>
                        @enderror

                        <input type="date" name="payed_at" value="{{ old('payed_at', $order->payed_at) }}">
                    </label>

                    <button type="submit">
                        {{ __('Save') }}
                    </button>
                </form>
        @endcan

        @can('delete', $order)
            <form action="{{ route('orders.update', $order) }}" method="POST">
                @method('delete')
                @csrf
                <button type="submit">
                    @svg('solid/trash', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{  __('Delete orders') }}
                </button>
            </form>
        @endcan

        @if($order->canBeAutoOrdered())

            <form action="{{ route('orders.auto_order', $order) }}" method="post">
                @csrf
                @if($order->canBeUpdated())
                    <button type="submit">{{ __('Update order') }}</button>
                @else
                    <button type="submit">{{ __('Place order') }}</button>
                @endif
            </form>
        @endif
    </header>

    <table>
        <thead>
        <th>{{ __('Date') }}</th>
        <th>{{ __('Title') }}</th>
        <th>{{ __('User') }}</th>
        <th>{{ __('Quantity') }}</th>
        <th>{{ __('Actions') }}</th>
        </thead>
        <tbody>
        @foreach($order->orderItems->sortBy('meal.date') as $orderItem)
            <tr>
                <td>{{ $orderItem->meal->date->isoFormat('L') }}</td>

                <td>{{ $orderItem->meal->title }}</td>

                <td>{{ $orderItem->user->name }}</td>

                <td>{{ $orderItem->quantity }}</td>

                <td>
                    <a href="{{route('order_items.edit', $orderItem)}}">
                        @svg('solid/pen', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                        <span class="sr-only">{{ __('Edit') }}</span>
                    </a>
                    <form method="post" action="{{ route('order_items.destroy', $orderItem) }}">
                        @csrf()
                        @method('delete')
                        <button type="submit">
                            @svg('solid/trash', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                            <span class="sr-only">{{ __('Delete') }}</span>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
