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
                                    <input type="date" class="form-control" id="from" value="{{ $from->toDateString() }}">
                                    <input type="hidden" name="from" id="from_raw" value="{{ $from->toDateString() }}">
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-group">
                                    <label for="to">{{ __('To') }}</label>
                                    <input type="date" class="form-control" id="to" value="{{ $to ? $to->toDateString() : '' }}">
                                    <input type="hidden" name="to" id="to_raw" value="{{ $to ? $to->toDateString() : '' }}">
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
                    <div class="container-fluid">
                        <div class="row py-3 border-top">
                            <div class="col-2"><strong>{{__('Date')}}</strong></div>
                            <div class="col-2"><strong>{{__('Status')}}</strong></div>
                            <div class="col-2"><strong>{{__('Title')}}</strong></div>
                            <div class="col-1 text-center"><strong>{{__('Quantity')}}</strong></div>
                            <div class="col-1 text-center"><strong>{{__('Price')}}</strong></div>
                            <div class="col-2"><strong>{{__('Ordered by')}}</strong></div>
                        </div>
                        @foreach($orders as $order)
                            <div class="row pb-3 border-top align-items-center">
                                <div class="col-2">
                                    {{ $order->date->format(__('futtertrog.d.m.Y')) }}
                                </div>
                                <div class="col-2">
                                    {{ __('futtertrog.status.' . $order->status) }}
                                </div>
                                <div class="col-6">
                                    @foreach($order->meals as $meal)
                                        <div class="row py-3 {{ $loop->last ? '' : ' border-bottom' }}">
                                            <div class="col-4">{{ $meal->title }}</div>
                                            <div class="col-2 text-center">{{ $meal->users()->sum('quantity') }}</div>
                                            <div class="col-2 text-center text-nowrap">{{ number_format($meal->price, 2, ',','.') }} €</div>
                                            <div class="col-3">
                                                @foreach($meal->users as $user)
                                                    <div class="text-nowrap">
                                                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a> ( {{ $user->pivot->quantity }} )
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-2">
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
                        @endforeach
                        <div class="row">
                            <div class="col-1  offset-6 text-center"><strong>{{ __('Sum') }}</strong></div>
                            <div class="col-1 text-center text-nowrap"><strong>{{ number_format($sum, 2, ',','.') }} €</strong></div>
                        </div>
                        @else
                            {{ __('No orders') }}
                        @endif
                    </div>
            </div>
        </div>

    </div>

@endsection
