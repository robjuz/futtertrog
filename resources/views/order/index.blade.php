@extends('layouts.app')

@section('content')


    <div class="container">

        <table class="table">
            <thead>
            <th>{{__('Date')}}</th>
            <th>{{__('Status')}}</th>
            <th>{{__('Title')}}</th>
            <th>{{__('Quantity')}}</th>
            <th>{{__('Ordered by')}}</th>

            </thead>
            <tbody>
            @foreach($orders as $order)
                <tr class="row-padding">
                    <th rowspan="{{ $order->meals->count() + 1 }}">
                        {{ $order->date->toDateString() }}
                    </th>
                    <td rowspan="{{ $order->meals->count() + 1 }}">{{ $order->status }}</td>
                </tr>

                @forelse($order->meals as $meal)
                    <tr class="{{ $loop->first ? 'row-padding' : '' }}">
                        <td>{{ $meal->title }}</td>
                        <td>{{ $meal->order_details->quantity }}</td>
                        <td>{{ implode(', ', $meal->users->pluck('name')->toArray()) }}</td>
                    </tr>
                @empty

                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            @endforeach
            </tbody>
        </table>

    </div>

@endsection