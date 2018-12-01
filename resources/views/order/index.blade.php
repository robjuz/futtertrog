@extends('layouts.app')

@section('content')


    <div class="container">

        <table class="table">
            <thead>
            <th>Datum</th>
            <th>Status</th>
            <th>Titel</th>
            <th>Menge</th>

            </thead>
            <tbody>
            @foreach($orders as $order)
                <tr class="row-padding">
                    <th rowspan="{{ $order->meals->count() + 1 }}">
                        <a href="{{ route('orders.show', $order) }}">{{ $order->date }}</a>
                    </th>
                    <td rowspan="{{ $order->meals->count() + 1 }}">{{ $order->status }}</td>
                </tr>

                @forelse($order->meals as $meal)
                    <tr class="{{ $loop->first ? 'row-padding' : '' }}">
                        <td>{{ $meal->title }}</td>
                        <td>{{ $meal->order_details->quantity }}</td>
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