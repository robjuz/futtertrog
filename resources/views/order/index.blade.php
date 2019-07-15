@extends('layouts.app')

@section('content')
    <main class="container order-index flex-grow-1">
        <div class="card">
            <h1 class="card-header d-flex justify-content-between align-items-center">
                {{ __('Order index') }}

                <a href="{{ route('order_items.create') }}" class="btn btn-link"> {{ __('Create order') }}</a>
            </h1>

            <div class="card-body">
                <form action="{{ route('orders.index') }}" method="get">
                    <div class="form-row align-items-end">
                        <div class="col-md">
                            <div class="form-group">
                                <label for="from">{{ __('From') }}</label>

                                <input type="date"
                                       class="form-control"
                                       name="from"
                                       id="from"
                                       value="{{ $from->toDateString() }}"
                                >
                            </div>
                        </div>

                        <div class="col-md">
                            <div class="form-group">
                                <label for="to">{{ __('To') }}</label>

                                <input type="date"
                                       class="form-control"
                                       name="to"
                                       id="to"
                                       value="{{ $to ? $to->toDateString() : '' }}"
                                >
                            </div>
                        </div>

                        <div class="col-md">
                            <div class="form-group">
                                <label for="user_id">{{ __('Filter by user') }}</label>
                                <select class="custom-select" name="user_id" id="user_id">
                                    <option
                                        {{ request('user_id', null) == null ? ' selected' : '' }}
                                        value=""
                                    >
                                        {{ __('Filter by user') }}
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
                        </div>

                        <div class="col-md-auto">
                            <div class="form-group">
                                <button type="submit" class="btn btn-secondary">{{ __('Search') }}</button>
                            </div>
                        </div>
                    </div>
                </form>

                @if($orders->isNotEmpty())
                    <div class="row py-3x">
                        <div class="col-6 col-md-2 text-md-center mb-2 mb-md-0"><strong>{{__('Date')}}</strong></div>
                        <div class="col-6 col-md-2 text-md-center mb-2 mb-md-0"><strong>{{__('Status')}}</strong></div>
                        <div class="col-12 col-md-8">
                            <div class="row">
                                <div class="col-4 col-md-4 text-md-center mb-2 mb-md-0"><strong>{{__('Title')}}</strong>
                                </div>
                                <div class="col-4 col-md-2 text-md-center mb-2 mb-md-0">
                                    <strong>{{__('Quantity')}}</strong></div>
                                <div class="col-4 col-md-2 text-md-center mb-2 mb-md-0"><strong>{{__('Price')}}</strong>
                                </div>
                                <div class="col-12 col-md-4 mb-2 mb-md-0"><strong>{{__('Ordered by')}}</strong></div>
                            </div>
                        </div>
                    </div>
                    @foreach($orders as $order)
                        <div class="row border-top border-primary py-3 align-items-center">
                            <div class="col-6 col-md-2 text-md-center">
                                {{ __('calendar.' . $order->date->englishDayOfWeek) }}<br>
                                {{ $order->date->format(__('futtertrog.date_format')) }}<br>
                                {{ $order->provider }}
                            </div>
                            <div class="col-6 col-md-2 text-md-center">
                                {{ __('futtertrog.status.' . $order->status) }}

                                @if ($order->status === \App\Order::STATUS_OPEN)
                                    @can('update', $order)
                                        <form action="{{ route('orders.update', $order) }}" method="POST">
                                            @method('put')
                                            @csrf
                                            <input type="hidden" name="status" value="{{ \App\Order::STATUS_ORDERED }}">
                                            <button type="submit" class="btn btn-link p-0 text-left text-md-center">
                                                {{ __('Mark as ordered') }}
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-12 col-md-8">
                                @foreach($order->orderItemsCompact() as $orderItem)
                                    <div class="row py-3 align-items-center {{ $loop->last ? '' : ' border-bottom' }}">
                                        <div class="col-6 col-md-4 text-md-center mb-2 mb-md-0">{{ $orderItem->meal->title }}</div>
                                        <div class="col-3 col-md-2 text-md-center mb-2 mb-md-0">{{ $orderItem->quantity }}</div>
                                        <div class="col-3 col-md-2 text-md-center mb-2 mb-md-0 text-nowrap">
                                            {{ number_format($orderItem->meal->price, 2, ',','.') }} €
                                        </div>
                                        <div class="col-12 col-md-4">
                                            @foreach($orderItem->users as $user)
                                                <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                                                @if(!$loop->last) <br> @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div class="row flex-row-reverse">
                        <div class="col-12 col-sm-6 col-md-4">
                            <strong class="mr-3">{{ __('Sum') }}</strong>
                            <strong>{{ number_format($sum, 2, ',','.') }} €</strong>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning" role="alert">
                        <strong> {{ __('No items found') }}</strong>
                    </div>
                @endif
            </div>
        </div>

    </main>

@endsection
