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
                <input type="date" name="from" id="from" value="{{ $from ? $from->toDateString() : '' }}">
            </div>

            <div>
                <label for="to">{{ __('To') }}</label>
                <input type="date" name="to" id="to" value="{{ $to ? $to->toDateString() : '' }}">
            </div>

            <x-user-select show-option-all="true"></x-user-select>

            <x-provider-select></x-provider-select>

            <div>
                <label for="status">
                    <span>{{__('Status')}}</span>
                    @error('status'))
                    <span>{{ $message }}</span>
                    @enderror
                </label>
                <select id="status" name="status">
                        <option value="" {{ request('status')  == null ? 'selected="selected"' : '' }}>
                            {{ __('All') }}
                        </option>
                    @foreach(\App\Order::$statuses as $status)
                        <option
                                value="{{ $status }}"
                                {{ request('status')  == $status ? 'selected="selected"' : '' }}
                        >
                            {{ __('futtertrog.status.' . $status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="payed">
                    <span>{{__('Payed')}}</span>
                    @error('payed'))
                    <span>{{ $message }}</span>
                    @enderror
                </label>
                <select id="payed" name="payed">
                    <option value="" {{ request('payed')  == null ? 'selected="selected"' : '' }}>
                        {{ __('All') }}
                    </option>
                    @foreach([0 => __('Not payed'), 1 => __('Payed')] as $key => $payed)
                        <option
                                value="{{ $key }}"
                                {{ request('status')  == $key ? 'selected="selected"' : '' }}
                        >
                            {{ $payed }}
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
                                <span class="order__provider order__header-item">
                            {{ $order->provider->getName() }}
                            </span>
                            @endif

                            <span class="order__header-item">{{ $order->getFormattedDate() }}</span>

                            <span class="order__header-item">{{ __('futtertrog.status.' . $order->status) }}</span>

                            <span class="order__header-item">{{ $order->payed_at ? __('Payed') : __('Not payed') }}</span>
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

        {{ $orders->links() }}
    @else
        <p> {{ __('No items found') }}</p>
    @endif
@endsection
