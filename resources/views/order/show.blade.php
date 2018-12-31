@extends('layouts.app')

@section('content')
    <div class="container">

        <h2>{{ __('Order') }} {{ $order->date->format('d F Y') }}</h2>

        @foreach($order->meals as $meal)
            <div class="row">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card w-100">
                        <h3 class="card-header d-flex justify-content-between">{{ $meal->title }}</h3>
                        <div class="card-body">
                            <p class="card-text">{{ $meal->description }}</p>
                        </div>
                        <h4 class="card-header">{{ __('Was ordered by:') }}</h4>
                        <ul class="list-group list-group-flush">
                            @foreach($meal->users as $user)
                                <li class="list-group-item">{{ $user->name }}</li>
                            @endforeach
                        </ul>
                    </div>

                </div>

                @endforeach

            </div>
@endsection