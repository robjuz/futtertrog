@extends('layouts.app')

@push('main-classes')
    dashboard
@endpush

@section('content')
    <h1>{{ __('Dashboard') }}</h1>

    <section id="today-orders" class="pot">
        <header>
            <h2>{{ __('Your order for today') }}</h2>
        </header>

        @if($todayOrders->count())
            <ul>
                @foreach($todayOrders as $order)
                    <li>
                        <h3>
                            {{ $order->quantity }} &times; {{ $order->meal->title }}
                        </h3>
                        @if (!(auth()->user()->settings->hideDashboardMealDescription))
                            <p>{{ $order->meal->description }}</p>
                        @endif
                        <p class="orderStatus">{{ __('Status')}}: {{__('futtertrog.orderStatus.'. $order->status) }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <p>{{ __('No orders for today') }}</p>
        @endif
    </section>

    <section class="pot">
        @include('user._balance')
    </section>

    <section id="upcoming-orders" class="pot">
        <header>
            <h2 id="future-meals">{{ __('Your upcoming orders') }}</h2>
        </header>

            @if($futureOrders->count())
                <ul>
                    @foreach($futureOrders->groupBy(function($orderItem) { return $orderItem->date->format(__('futtertrog.date_format')); }) as $date => $orders)
                        <li>
                            <h3>{{ $date }}</h3>

                            @foreach( $orders as $order)
                                <h4>
                                    {{ $order->quantity }} &times; {{ $order->meal->title }}
                                </h4>

                                @if (!(auth()->user()->settings->hideDashboardMealDescription ?? false))
                                    <p>{{ $order->meal->description }}</p>
                                @endif
                                <p class="orderStatus">{{ __('Status')}}: {{__('futtertrog.orderStatus.'. $order->status) }}</p>
                            @endforeach
                        </li>
                    @endforeach
                </ul>

                {{ $futureOrders->links() }}

            @else
                <p>{{ __('No upcoming orders') }}</p>

                <a href="{{ route('meals.index') }}">{{ __('Place order') }}</a>
            @endif
    </section>

    @if(auth()->user()->is_admin)
        <x-system-balance />
    @endif

    <section id="deposit-history" class="pot">
        @include('partials.deposit_history')
    </section>
<?php /*
        <section id="deposit-history">
            @include('partials.order_history', ['orders' => $ordersHistory])
        </section>
*/?>
@endsection
