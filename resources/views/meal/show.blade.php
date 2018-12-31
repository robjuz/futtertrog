@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="card-deck">

            <div class="card">
                <div class="card-header d-flex justify-content-between"><span>{{ $meal->date->format('d F Y') }}</span><span>{{ number_format($meal->price, 2, ',', '.' }} €</span></div>
                <div class="card-body">
                    <h5 class="card-title">{{ $meal->title }}</h5>
                    <p class="card-text">{{ $meal->description }}</p>
                </div>
            </div>

            <div class="card">
                <h5 class="card-header">{{ __('Was ordered by:') }}</h5>
                <ul class="list-group list-group-flush">
                    @foreach($meal->users as $user)
                        <li class="list-group-item">{{ $user->name }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection