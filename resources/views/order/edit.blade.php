@extends('layouts.app')

@section('content')
    <div class="pot">
        <header class="order__header">
            <h1>
                @if($order->provider)
                    <span class="order__provider">{{ $order->provider->getName() }}</span>
                @endif

                <span>{{ $order->getFormattedDate() }}</span>
            </h1>

            @can('create', \App\OrderItem::class)
                <a href="{{ route('order_items.create') }}">
                    @svg('solid/plus', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                    {{ __('Create order') }}
                </a>
            @endcan

        </header>

        @can('update', $order)
            <div class="order__subheader">
                <form class="order__form" action="{{ route('orders.update', $order) }}" method="POST">
                    @method('put')
                    @csrf
                    <input type="hidden" name="status"
                           value="{{ $order->status === \App\Order::STATUS_ORDERED ? \App\Order::STATUS_OPEN : \App\Order::STATUS_ORDERED }}">

                    <button type="submit">
                        {{ $order->status === \App\Order::STATUS_ORDERED ? __('Mark as open') : __('Mark as ordered') }}
                    </button>
                </form>

                <form class="order__form" action="{{ route('orders.update', $order) }}" method="POST">
                    @method('put')
                    @csrf
                    <label style="flex: 1;">
                        <span>{{ __('Payed at') }}</span>
                        @error('payed_at')
                        <span>{{ $message }}</span>
                        @enderror

                        <input type="date" name="payed_at" value="{{ old('payed_at', $order->payed_at) }}">
                    </label>

                    <div style="flex: 1;">
                        <label for="user_id">
                            <span>{{ __('Payed by') }}</span>
                            @error('user_id')
                            <span>{{ $message }}</span>
                            @enderror
                        </label>
                        <select id="user_id" name="user_id">
                            <option value="">---</option>
                            @foreach ($users as $user)
                                <option
                                        value="{{ $user->id }}"
                                        {{ old('user_id', $order->user_id) == $user->id ? 'selected' : ''}}

                                >
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit">
                        {{ __('Save') }}
                    </button>
                </form>
            </div>
        @endcan
        @foreach($order->orderItems->groupBy(['meal' => fn($meal) => $meal->date->isoFormat('L'), 'meal_id']) as $date => $mealsForDate)

            <h3 class="order-item__date {{ $loop->first ? '' : ' order-item--border' }}">{{ $date }}</h3>

            <ul class="order-items">
                @foreach($mealsForDate->sortKeys() as $orderItems)
                    @php
                        $meal = $orderItems->first()->meal;
                    @endphp

                    <li class="order-item">
                        <h4 class="order-item__meal">

                            <span>{{ $meal->title }}</span>
                            <span>{{ $orderItems->sum('quantity') }} &times; {{ $meal->price }}</span>

                        </h4>

                        <ul class="order-item__users">
                            @foreach($orderItems as $orderItem)
                                <li class="order-item__user">

                                    <div>{{ $orderItem->user->name }} &times; {{ $orderItem->quantity }}</div>

                                    <div class="order-item__actions">
                                        <a href="{{route('order_items.edit', $orderItem)}}">
                                            @svg('solid/pen', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                                            <span class="sr-only">{{ __('Edit') }}</span>
                                        </a>
                                        <form method="post" action="{{ route('order_items.destroy', $orderItem) }}">
                                            @csrf()
                                            @method('delete')
                                            <button type="submit">
                                                @svg('solid/trash', ['role="presentation"', 'aria-hidden="true"',
                                                'focusable="false"'])
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endforeach

        <div class="order__subtotal">
            {{ $order->subtotal }}
        </div>

        <div class="order__footer">
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
        </div>
    </div>
@endsection
