@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">


            <div class="col-md-6 mb-3">

                <div class="card mb-3">
                    <div class="card-header">{{ __('Your balance') }}</div>

                    <div class="card-body">
                        <span class="{{ $balance > 0 ? 'text-success' : 'text-danger' }} text-nowrap">
                        {{ number_format($balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">{{ __('Your today order') }}</div>

                    <div class="card-body">
                        @if($todayOrders->count())

                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">{{ __('Title') }}</th>
                                    <th scope="col">{{ __('Description') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($todayOrders as $order)
                                    <tr>
                                        <th scope="row" class="text-nowrap">{{ $order->meal->title }}</th>
                                        <td>{{ $order->meal->description }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="card bg-danger">

                                <div class="card-body py-1">
                                    {{ __('No orders for today') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">{{ __('Your upcoming orders') }}</div>

                    <div class="card-body">
                        <div class="card-deck flex-column">
                            @if($futureOrders->count())
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ __('Date') }}</th>
                                        <th scope="col">{{ __('Order') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($futureOrders->groupBy(function($orderItem) { return $orderItem->order->date->format(__('futtertrog.d.m.Y')); }) as $date => $orders)

                                        @foreach( $orders as $order)
                                            <tr>
                                                @if ($loop->iteration == 1)
                                                    <th scope="row" rowspan="{{ $orders->count() }}">
                                                        {{ $date }}
                                                    </th>
                                                @endif
                                                <td>
                                                    <strong>{{ $order->meal->title }}</strong> ( {{ $order->quantity }} )
                                                    <div title="{{ $order->meal->description }}">
                                                        {{ $order->meal->description }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>

                                {{ $futureOrders->links() }}
                            @else
                                <div class="card bg-danger">

                                    <div class="card-body py-1">
                                        {{ __('No upcoming orders') }}
                                    </div>
                                </div>
                                <div class="card py-2 mt-2">
                                    <a href="{{ route('meals.index') }}"
                                       class="btn btn-primary">{{ __('Place order') }}</a>
                                </div>
                            @endif
                        </div>
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
