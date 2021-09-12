@extends('layouts.app')

@section('content')
    <h1>{{ __('Order index') }}</h1>

    <nav class="sub-menu">
        <a href="{{ route('order_items.create') }}"> {{ __('Create order') }}</a>
    </nav>

    <section>
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
                        {{ __('All users') }}
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
    </section>

    <section>
        @if($orders->isNotEmpty())
            <table class="orders-overview">
                <thead>
                <tr>
                    <th>{{__('Date')}}</th>
                    <th class="collapsible">{{__('Provider')}}</th>
                    <th class="collapsible">{{__('Status')}}</th>
                    <th class="collapsible">{{__('Quantity')}}</th>
                    <th>{{__('Title')}}</th>
                    <th class="collapsible">{{__('Ordered by')}}</th>
                    <th class="money collapsible">{{__('Price')}}</th>
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
                                <td {{ count($order->orderItemsCompact()) > 1 ? ' rowspan=' . count($order->orderItemsCompact()) : ''}}>
                                    @can('update', $order)
                                        <a href="{{ route('orders.edit', $order) }}">
                                            {{ __('calendar.' . $order->date->englishDayOfWeek) }}<br>
                                            {{ $order->date->format(__('futtertrog.date_format')) }}
                                        </a>
                                    @else
                                        {{ __('calendar.' . $order->date->englishDayOfWeek) }}<br>
                                        {{ $order->date->format(__('futtertrog.date_format')) }}
                                    @endcan
                                </td>

                                <td
                                    class="collapsible"
                                    {{ count($order->orderItemsCompact()) > 1 ? ' rowspan=' . count($order->orderItemsCompact()) : ''}}
                                >
                                    {{ $order->provider ? $order->provider->getName() : '' }}
                                </td>

                                <td
                                    class="collapsible"
                                    {{ count($order->orderItemsCompact()) > 1 ? ' rowspan=' . count($order->orderItemsCompact()) : ''}}
                                >

                                    @can('update', $order)
                                        <form action="{{ route('orders.update', $order) }}" method="POST">
                                            @method('put')
                                            @csrf
                                            @if($order->status === \App\Order::STATUS_ORDERED)
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="{{ \App\Order::STATUS_OPEN }}"
                                                >
                                                <button type="submit" title="{{ __('Mark as open') }}">
                                                    {{ __('futtertrog.status.' . $order->status) }}

                                                    <span class="sr-only">{{ __('Mark as open') }}</span>
                                                </button>
                                            @else
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="{{ \App\Order::STATUS_ORDERED }}"
                                                >
                                                <button type="submit" title="{{ __('Mark as ordered') }}">
                                                    {{ __('futtertrog.status.' . $order->status) }}

                                                    <span class="sr-only">{{ __('Mark as ordered') }}</span>
                                                </button>
                                            @endif
                                        </form>
                                    @else
                                        {{ __('futtertrog.status.' . $order->status) }}
                                    @endcan
                                </td>
                            @endif
                            <td class="collapsible">
                                {{ $orderItem->quantity }}
                            </td>

                            <td>
                                @can('edit', $orderItem->meal)
                                    <a href="{{ route('meals.edit', $orderItem->meal) }}">{{ $orderItem->meal->title }}</a>
                                @else
                                    {{ $orderItem->meal->title }}
                                @endcan
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

                            <td class="money collapsible">
                                @money($orderItem->meal->price)
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>

                <tfoot>
                <tr>
                    <td colspan="6" class="money">
                        {{ __('Sum') }}: @money($sum)
                    </td>
                </tr>
                </tfoot>
            </table>

            @if($orders->canBeAutoOrdered())

                <form action="{{ route('orders.auto_order') }}" method="post">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                    <input type="hidden" name="to" value="{{ $to ? $to->toDateString() : '' }}">

                    <button type="submit">{{ __('Place order') }}</button>
                </form>
            @endif
        @else
            <p> {{ __('No items found') }}</p>
        @endif
    </section>
@endsection
