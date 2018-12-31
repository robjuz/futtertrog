@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                {{ __('Order index') }}
            </div>
            <div class="card-body">
                @if($orders)
                    <div class="container-fluid">
                        <div class="row py-3 border-top">
                            <div class="col-2"><strong>{{__('Date')}}</strong></div>
                            <div class="col-2"><strong>{{__('Status')}}</strong></div>
                            <div class="col-2"><strong>{{__('Title')}}</strong></div>
                            <div class="col-2 text-center"><strong>{{__('Quantity')}}</strong></div>
                            <div class="col-2"><strong>{{__('Ordered by')}}</strong></div>
                        </div>
                        @foreach($orders as $order)
                            <div class="row pb-3 border-top align-items-center">
                                <div class="col-2">
                                    {{ $order->date->format('d F Y') }}
                                </div>
                                <div class="col-2">
                                    {{ __('futtertrog.status.' . $order->status) }}
                                </div>
                                <div class="col-6">
                                    @foreach($order->meals as $meal)
                                        <div class="row py-3 border-bottom">
                                            <div class="col-4">{{ $meal->title }}</div>
                                            <div class="col-4 text-center">{{ $meal->order_details->quantity }}</div>
                                            <div class="col-4">{{ implode(', ', $meal->users->pluck('name')->toArray()) }}</div>
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
                        @else
                            {{ __('No orders') }}
                        @endif
                    </div>
            </div>
        </div>

    </div>

@endsection