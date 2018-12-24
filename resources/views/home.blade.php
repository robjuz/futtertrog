@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-6 mb-3">

                <div class="card">
                    <div class="card-header">Dein Guthaben</div>

                    <div class="card-body">
                        <span class="{{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

            </div>

            <div class="col-md-6 mb-3">

                <div class="card">
                    <div class="card-header">Deine Bestellung für heute</div>

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
                                <div class="card bg-danger">

                                    <div class="card-body py-1">
                                        Du hast kein Essen für heute
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-6 offset-md-6 mb-3">

                <div class="card">
                    <div class="card-header">Deine Bestellungen für weitere Tage</div>

                    <div class="card-body">
                        <div class="card-deck flex-column">
                            @forelse($futureMeals as $meal)
                                <table>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Bestellung</th>
                                    </tr>
                                    <tbody>
                                        <tr>
                                            <td>
                                                {{ $meal->date->format('d.m.y') }}
                                            </td>
                                            <td>
                                                <div>
                                                    {{ $meal->title }}
                                                </div>
                                                <div title="{{ $meal->description }}">
                                                    {{ $meal->description }}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @empty
                                <div class="card bg-danger">

                                    <div class="card-body py-1">
                                        Du hast kein Essen für weitere Tage bestellt.
                                    </div>
                                </div>
                                <div class="card py-2 mt-2">
                                    <a href="{{ route('meals.index') }}" class="btn btn-primary">Bestellen</a>
                                </div>
                            @endforelse
                            @if($count > 5)
                                <a href="{{ route('meals.index') }}">Weitere Bestellungen</a>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
