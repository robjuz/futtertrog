@extends('layouts.app')

@section('content')
    <main class="container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <h1 class="h5 card-header d-flex justify-content-between">
                        {{ $meal->title }}
{{--                        <span>{{ $meal->date->format('d F Y') }}</span>--}}
                    </h1>
                    <div class="card-body">
                        <div class="card-title d-flex justify-content-between">
                            <span>{{ $meal->provider }}</span>
                            <span>{{ $meal->date_from->format(trans('futtertrog.date_format')) }} - {{ $meal->date_to->format(trans('futtertrog.date_format')) }}</span>
                            <span class="text-nowrap">{{ number_format($meal->price, 2, ',', '.') }} €</span>
                        </div>
                        <p class="card-text">{{ $meal->description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
