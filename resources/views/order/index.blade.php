@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                {{ __('Order index') }}
            </div>
            <div class="card-body">
                <div class="py3-">
                    <form action="{{ route('orders.index') }}" method="get">
                        <div class="form-row align-items-end">
                            <div class="col-md">
                                <div class="form-group">
                                    <label for="from">{{ __('From') }}</label>
                                    <input type="date" class="form-control" name="from" id="from" value="{{ $from }}">
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-group">
                                    <label for="to">{{ __('To') }}</label>
                                    <input type="date" class="form-control" name="to" id="to" value="{{ $to }}">
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if($orders)
                    <div class="container-fluid p-0">
                        @foreach($orders as $order)
                            <div class="card">
                                <div class="card-header d-flex justify-content-between">
                                    <div>
                                        {{ $order->date->format(__('futtertrog.d.m.Y')) }}
                                    </div>
                                    <div>
                                        {{ $order->provider }}
                                    </div>
                                    <div class="text-right">
                                        {{ __('futtertrog.status.' . $order->status) }}
                                        @if ($order->status === \App\Order::STATUS_OPEN)
                                            @can('update', $order)
                                                <form action="{{ route('orders.update', $order) }}" method="POST">
                                                    @method('put')
                                                    @csrf
                                                    <input type="hidden" name="status"
                                                           value="{{ \App\Order::STATUS_ORDERED }}">
                                                    <button type="submit" class="btn btn-link p-0">
                                                        {{ __('Mark as ordered') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-title font-weight-bold">
                                        <div class="row">
                                            <div class="col">{{ __('Title') }}</div>
                                            <div class="col-1 text-center">{{ __('Quantity') }}</div>
                                            <div class="col-1 text-center text-nowrap">{{ __('Price') }}</div>
                                            <div class="col-3">{{ __('Ordered by') }}</div>
                                        </div>
                                    </div>

                                    @foreach($order->meals as $meal)
                                        <div class="row py-3 border-top">
                                            <div class="col">
                                                <strong>
                                                    {{ $meal->title }}
                                                </strong>
                                                <p> {{ $meal->description }}</p>
                                            </div>
                                            <div class="col-1 text-center">{{ $meal->users->sum('pivot.quantity') }}</div>
                                            <div class="col-1 text-center text-nowrap">{{ number_format($meal->price, 2, ',','.') }}
                                                €
                                            </div>
                                            <div class="col-3">
                                                @foreach($meal->users as $user)
                                                    <div class="text-nowrap">
                                                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                                                        ( {{ $user->pivot->quantity }} )
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="row flex-row-reverse border-top pt-3">
                                        <div class="col-3"></div>
                                        <div class="font-weight-bold col-1 text-center text-nowrap">
                                            {{ number_format($order->meals->sum('price'), 2, ',','.') }} €
                                        </div>
                                        <div class="font-weight-bold col-1 text-center">
                                            {{ $order->meals->sum(function($meal) { return $meal->users->sum('pivot.quantity'); }) }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                        <div class="text-right bg-primary p-3 text-white">
                            <strong>{{ __('Sum') }} {{ number_format($sum, 2, ',','.') }} €</strong>
                        </div>
                        @else
                            {{ __('No orders') }}
                        @endif
                    </div>
            </div>
        </div>

    </div>

@endsection
