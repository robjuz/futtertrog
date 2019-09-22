@extends('layouts.app')

@section('content')
    <h1>{{ __('Order index') }}</h1>

    <a href="{{ route('order_items.create') }}"> {{ __('Create order') }}</a>

    <form action="{{ route('orders.index') }}" method="get" class="orders-overview-filter">

        <div>
            <label for="from">{{ __('From') }}</label>
            <input type="date" name="from" id="from" value="{{ $from->toDateString() }}">
        </div>

        <div>
            <label for="to">{{ __('To') }}</label>
            <input type="date" name="to" id="to" value="{{ $to ? $to->toDateString() : '' }}">
        </div>

        <div>
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
        </div>

        <button type="submit">{{ __('Search') }}</button>
    </form>

    <section>
    @if($orders->isNotEmpty())
        <table class="orders-overview">
            <thead>
                <tr>
                    <th>{{__('Date')}}</th>
                    <th class="collapsible">{{__('Status')}}</th>
                    <th class="collapsible">{{__('Quantity')}}</th>
                    <th>{{__('Title')}}</th>
                    <th class="collapsible">{{__('Price')}}</th>
                    <th>{{__('Ordered by')}}</th>
                </tr>
            </thead>

            <tbody>
                @php $decoration = 'a'; @endphp
                @foreach($orders as $order)
                    @foreach($order->orderItemsCompact() as $orderItem)
                        @php if(count($order->orderItemsCompact()) == 1 OR $loop->first)
                                $decoration == 'a' ? $decoration = 'b' : $decoration = 'a'; @endphp
                        <tr class="decoration-{{$decoration}}">
                            @if(count($order->orderItemsCompact()) == 1 OR $loop->first)
                                <td
                                    @if(count($order->orderItemsCompact()) > 1)
                                        rowspan="{{count($order->orderItemsCompact())}}"
                                    @endif
                                >
                                    @can('update', $order)
                                        <a href="{{ route('orders.edit', $order) }}">
                                    @endcan
                                        {{ __('calendar.' . $order->date->englishDayOfWeek) }},
                                        {{ $order->date->format(__('futtertrog.date_format')) }}
                                    @can('update', $order)
                                        </a>
                                    @endcan
                                </td>

                                <td class="collapsible"
                                    @if(count($order->orderItemsCompact()) > 1)
                                    rowspan="{{count($order->orderItemsCompact())}}"
                                    @endif
                                >
                                    {{ __('futtertrog.status.' . $order->status) }}
                                </td>
                            @endif

                            <td class="collapsible">
                                {{ $orderItem->quantity }}
                            </td>
                            <td>
                                {{ $orderItem->meal->title }}
                            </td>

                            <td class="money collapsible">
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
                    <td colspan="5" class="money">
                        {{ __('Sum') }}: {{ number_format($sum, 2, ',','.') }} €
                    </td>
                    <td><?php /* intentionally left empty to align sum with prices */ ?></td>
                </tr>
            </tfoot>
        </table>
    @else
        <p> {{ __('No items found') }}</p>
    @endif
    </section>
@endsection
