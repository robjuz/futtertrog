@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                {{ __('Order index') }}
            </div>
            <div class="card-body">
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

                @if($orders)
                    <div class="container-fluid">
                        <div class="row py-3">
                            <div class="col-2 text-center"><strong>{{__('Date')}}</strong></div>
                            <div class="col-2 text-center"><strong>{{__('Status')}}</strong></div>
                            <div class="col-8">
                                <div class="row">
                                    <div class="col-4 text-center"><strong>{{__('Title')}}</strong></div>
                                    <div class="col-2 text-center"><strong>{{__('Quantity')}}</strong></div>
                                    <div class="col-2 text-center"><strong>{{__('Price')}}</strong></div>
                                    <div class="col-4"><strong>{{__('Ordered by')}}</strong></div>
                                </div>
                            </div>
                        </div>
                        @foreach($orders as $order)
                            <div class="row border-top border-primary align-items-center">
                                <div class="col-2 text-center">
                                    {{ $order->date->format(__('futtertrog.d.m.Y')) }}<br>
                                    {{ $order->provider }}
                                </div>
                                <div class="col-2 text-center">
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
                                <div class="col-8">
                                    @foreach($order->meals as $meal)
                                        <div class="row py-3 align-items-center {{ $loop->last ? '' : ' border-bottom' }}">
                                            <div class="col-4 text-center">{{ $meal->title }}</div>
                                            <div class="col-2 text-center">{{ $meal->users()->sum('quantity') }}</div>
                                            <div class="col-2 text-center text-nowrap">{{ number_format($meal->price, 2, ',','.') }}
                                                €
                                            </div>
                                            <div class="col-4">
                                                @foreach($meal->users as $user)
                                                    <div>
                                                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                                                        ( {{ $user->pivot->quantity }} )
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="row">
                            <div class="col-1  offset-6 text-center"><strong>{{ __('Sum') }}</strong></div>
                            <div class="col-1 text-center text-nowrap"><strong>{{ number_format($sum, 2, ',','.') }}
                                    €</strong></div>
                        </div>
                        @else
                            {{ __('No orders') }}
                        @endif
                    </div>
            </div>
        </div>

    </div>

@endsection
