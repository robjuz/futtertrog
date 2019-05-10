@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">


            <div class="col-md-6">

                <section class="mb-3">
                    <div class="card">
                        <h2 class="card-header">{{ __('Your balance') }}</h2>

                        <div class="card-body">
                            <div class="{{ $balance > 0 ? 'text-success' : 'text-danger' }} h5 text-nowrap">
                                {{ number_format($balance, 2, ',','.') }} €
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-3">
                    <div class="card">
                        <h2 class="card-header">{{ __('Your order for today') }}</h2>

                        @if($todayOrders->count())
                            <ul class="list-group list-group-flush">
                                @foreach($todayOrders as $order)
                                    <li class="list-group-item {{ $loop->last ? '' : 'border-bottom-0' }}">
                                        <h3 class="h5">
                                            {{ $order->quantity }} &times; {{ $order->meal->title }}
                                        </h3>
                                        @if (!(auth()->user()->settings[\App\User::SETTING_HIDE_DASHBOARD_MEAL_DESCRIPTION] ?? false))
                                            <p>{{ $order->meal->description }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="alert alert-danger m-0" role="alert">
                                <strong> {{ __('No orders for today') }}</strong>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="mb-3">
                    <div class="card">
                        <h2 id="future-meals" class="card-header">{{ __('Your upcoming orders') }}</h2>

                        @if($futureOrders->count())
                            <ul class="list-group list-group-flush">
                                @foreach($futureOrders->groupBy(function($orderItem) { return $orderItem->order->date->format(__('futtertrog.date_format')); }) as $date => $orders)
                                    <li class="list-group-item {{ $loop->last ? ' border-bottom-0' : '' }}">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <h3 class="md-dark h5 d-flex d-lg-block justify-content-center mb-3 mb-lg-0">{{ $date }}</h3>
                                            </div>
                                            <div class="col-lg-8">
                                                @foreach( $orders as $order)
                                                    <section class="{{ $loop->last ? '' : ' mb-3 border-bottom' }}">

                                                        <h3 class="h5">
                                                            {{ $order->quantity }} &times; {{ $order->meal->title }}
                                                        </h3>

                                                        @if (!(auth()->user()->settings[\App\User::SETTING_HIDE_DASHBOARD_MEAL_DESCRIPTION] ?? false))
                                                            <p>{{ $order->meal->description }}</p>
                                                        @endif
                                                    </section>
                                                @endforeach
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            <nav>
                                {{ $futureOrders->links() }}
                            </nav>
                        @else
                            <div class="alert alert-danger m-0" role="alert">
                                <strong>{{ __('No upcoming orders') }}</strong>
                            </div>
                            <div class="card py-2 mt-2 mx-2">
                                <a href="{{ route('meals.index') }}"
                                   class="btn btn-primary">{{ __('Place order') }}</a>
                            </div>
                        @endif
                    </div>
                </section>

            </div>

            <div class="col-md-6">
                <section class="mb-3">
                    @include('partials.deposit_history')
                </section>

                <section class="mb-3">
                    @include('partials.order_history', ['orders' => $ordersHistory])
                </section>
            </div>

        </div>
    </div>
@endsection
