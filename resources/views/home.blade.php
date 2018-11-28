@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-6">

                <div class="card">
                    <h2 class="card-header">Dein Guthaben</h2>

                    <div class="card-body">
                        <span class="h2 {{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

            </div>

            <div class="col-md-6">

                <div class="card">
                    <h2 class="card-header">Deine Bestellung für heute</h2>

                    <div class="card-body">
                        <div class="card-deck">
                            @forelse($meals as $meal)
                                <div class="card">
                                    <div class="card-header">{{ $meal->title }}</div>

                                    <div class="card-body">
                                        {{ $meal->description }}
                                    </div>
                                </div>
                            @empty
                                <div class="card text-white bg-danger">

                                    <div class="card-body">
                                        Du hast keine essen für heute
                                        <font-awesome-icon size="2x" icon="sad-tear"></font-awesome-icon>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
