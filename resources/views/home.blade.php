@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">


            <div class="col-md-6 mb-3">

                <div class="card mb-3">
                    <h2 class="card-header">{{ __('Your balance') }}</h2>

                    <div class="card-body">
                        <span class="{{ $balance > 0 ? 'text-success' : 'text-danger' }} text-nowrap">
                        {{ number_format($balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

                <div class="card mb-3">
                    <h2 class="card-header">{{ __('Your today order') }}</h2>

                    <div class="card-body">
                        @if($todayOrders->count())
                            @foreach($todayOrders as $order)
                                <section class="{{ $loop->last ? '' : ' mb-3 border-bottom' }}">
                                    <h3 class="h5">
                                        {{ $order->meal->title }}
                                        <small>( {{ $order->quantity }} )</small>
                                    </h3>
                                    <p>{{ $order->meal->description }}</p>
                                </section>
                            @endforeach
                        @else

                            <div class="alert alert-danger m-0" role="alert">
                                <strong> {{ __('No orders for today') }}!</strong>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <h2 class="card-header">{{ __('Your upcoming orders') }}</h2>

                    <div class="card-body">
                        @if($futureOrders->count())

                            @foreach($futureOrders->groupBy(function($orderItem) { return $orderItem->order->date->format(__('futtertrog.d.m.Y')); }) as $date => $orders)
                                <div class="row {{ $loop->last ? '' : ' mb-3 border-bottom' }}">
                                    <div class="col-lg-4">
                                        <h3 class="h5 d-flex d-lg-block justify-content-center mb-3 mb-lg-0">{{ $date }}</h3>
                                    </div>
                                    <div class="col-lg-8">
                                        @foreach( $orders as $order)
                                            <section class="{{ $loop->last ? '' : ' mb-3 border-bottom' }}">

                                                <h3 class="h5">
                                                    {{ $order->meal->title }}
                                                    <small>( {{ $order->quantity }} )</small>
                                                </h3>

                                                <p>{{ $order->meal->description }}</p>
                                            </section>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{ $futureOrders->links() }}
                        @else
                            <div class="alert alert-danger" role="alert">
                                <strong>{{ __('No upcoming orders') }}</strong>
                            </div>
                            <div class="card py-2 mt-2">
                                <a href="{{ route('meals.index') }}"
                                   class="btn btn-primary">{{ __('Place order') }}</a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <div class="col-md-6 mb-3">
                <div class="mb-3">
                    @include('deposit_history')
                </div>

                @include('order_history', ['orders' => $ordersHistory])
            </div>

        </div>
    </div>
@endsection
