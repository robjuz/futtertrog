@extends('layouts.app')

@section('content')
    <h1>{{ __('Order index') }}</h1>

    <a href="{{ route('order_items.create') }}"> {{ __('Create order') }}</a>

    <form action="{{ route('orders.index') }}" method="get">

        <label for="from">{{ __('From') }}</label>
        <input type="date" name="from" id="from" value="{{ $from->toDateString() }}">

        <label for="to">{{ __('To') }}</label>
        <input type="date" name="to" id="to" value="{{ $to ? $to->toDateString() : '' }}">

        <label for="user_id">{{ __('Filter by user') }}</label>
        <select name="user_id" id="user_id">
            <option value="" {{ request('user_id', null) == null ? ' selected' : '' }}>
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

        <button type="submit">{{ __('Search') }}</button>
    </form>

    <section>
    @if($orders->isNotEmpty())
        <table class="orders-overview">
            <thead>
                <tr>
                    <th>{{__('Date')}}</th>
                    <th>{{__('Status')}}</th>
                    <th>{{__('Quantity')}}</th>
                    <th>{{__('Title')}}</th>
                    <th>{{__('Price')}}</th>
                    <th>{{__('Ordered by')}}</th>
                </tr>
            </thead>

            <tbody>
                @foreach($orders as $order)
                    @foreach($order->orderItemsCompact() as $orderItem)
                        <tr>
                            @if(count($order->orderItemsCompact()) == 1 OR $loop->first)
                                <td
                                    @if(count($order->orderItemsCompact()) > 1)
                                        rowspan="{{count($order->orderItemsCompact())}}"
                                    @endif
                                >
                                    {{ __('calendar.' . $order->date->englishDayOfWeek) }},
                                    {{ $order->date->format(__('futtertrog.date_format')) }}
                                </td>

                                <td
                                    @if(count($order->orderItemsCompact()) > 1)
                                    rowspan="{{count($order->orderItemsCompact())}}"
                                    @endif
                                >
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
                                </td>
                            @endif

                            <td>
                                {{ $orderItem->quantity }}
                            </td>
                            <td>
                                {{ $orderItem->meal->title }}
                            </td>

                            <td>
                                {{ number_format($orderItem->meal->price, 2, ',','.') }} €
                            </td>

                            <td>
                                <ul>
                                    @foreach($orderItem->users as $user)
                                        <li>
                                            <a href="{{ route('users.show', $user) }}">
                                                {{ $user->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="6">
                        {{ __('Sum') }}: {{ number_format($sum, 2, ',','.') }} €
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <p> {{ __('No items found') }}</p>
    @endif
    </section>
@endsection
