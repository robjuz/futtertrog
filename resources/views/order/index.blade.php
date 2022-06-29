@extends('layouts.app')

@section('content')
    <h1>{{ __('Order index') }}</h1>

    <nav class="sub-menu">
        <a href="{{ route('order_items.create') }}"> {{ __('Create order') }}</a>
    </nav>

    <section>
        <form action="{{ route('orders.index') }}" method="get" class="orders-overview-filter">

            <div>
                <label for="from">{{ __('From') }}</label>
                <input type="date" name="from" id="from" value="{{ $from->toDateString() }}">
            </div>

            <div>
                <label for="to">{{ __('To') }}</label>
                <input type="date" name="to" id="to" value="{{ $to ? $to->toDateString() : '' }}">
            </div>

            <div>
                <label for="user_id">{{ __('Filter by user') }}</label>
                <select name="user_id" id="user_id">
                    <option value="" {{ request('user_id', null) == null ? ' selected' : '' }}>
                        {{ __('All users') }}
                    </option>
                    @foreach($users as $user)
                        <option
                                value="{{ $user->id }}"
                                {{ request('user_id') == $user->id ? ' selected' : '' }}
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit">{{ __('Search') }}</button>
        </form>
    </section>

    @if($orders->isNotEmpty())
        <ul class="orders">
            @foreach($orders as $order)
                <li class="pot order">
                    <header class="order__header">
                        <h2>
                            @if($order->provider)
                                <span class="order__provider">
                            {{ $order->provider->getName() }}
                            </span>
                            @endif

                            <span>{{ $order->getFormattedDate() }}</span>

                            <span>{{ __('futtertrog.status.' . $order->status) }}</span>
                        </h2>
                        @can('update', $order)
                            <a href="{{ route('orders.edit', $order) }}" class="order__header-link">
                                {{ __('Edit') }}
                            </a>
                        @endcan
                    </header>

                    <ul class="order-items">
                        @foreach($order->orderItems->groupBy(['meal.date.timestamp', 'meal_id']) as $mealsForDate)
                            @foreach($mealsForDate as $orderItems)
                                @php
                                    $meal = $orderItems->first()->meal;
                                @endphp

                                <li class="order-item {{ $loop->first ? ' order-item--border' : '' }}">
                                    @if($loop->first)
                                    <h3 class="order-item__date">{{ $meal->date->isoFormat('L') }}</h3>
                                    @else
                                    <span></span>
                                    @endif

                                    <h4 class="order-item__meal">

                                        <span>{{ $meal->title }}</span>
                                        <span>{{ $orderItems->sum('quantity') }} &times; {{ $meal->price }}</span>

                                    </h4>

                                    <ul class="order-item__users">
                                        @foreach($orderItems as $orderItem)
                                            <li class="order-item__user">
                                                <span>{{ $orderItem->user->name }}</span>

                                                 <span>&times; {{ $orderItem->quantity }}  </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>

                        <div class="order__subtotal">
                            {{ $order->subtotal }}
                        </div>
                </li>
            @endforeach
        </ul>
    @else
        <p> {{ __('No items found') }}</p>
    @endif
@endsection
