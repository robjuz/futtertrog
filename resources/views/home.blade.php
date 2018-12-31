@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-6 mb-3">

                <div class="card">
                    <div class="card-header">{{ __('Your balance') }}</div>

                    <div class="card-body">
                        <span class="{{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

            </div>

            <div class="col-md-6 mb-3">

                <div class="card">
                    <div class="card-header">{{ __('Your today order') }}</div>

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
                                        {{ __('No orders for today') }}
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-6 offset-md-6 mb-3">

                <div class="card">
                    <div class="card-header">{{ __('Your upcoming orders') }}</div>

                    <div class="card-body">
                        <div class="card-deck flex-column">
                            @forelse($futureMeals as $meal)
                                <table>
                                    <tr>
                                        <th scope="col">{{ __('Date') }}</th>
                                        <th scope="col">{{ __('Order') }}</th>
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
                                        {{ __('No upcoming orders') }}
                                    </div>
                                </div>
                                <div class="card py-2 mt-2">
                                    <a href="{{ route('meals.index') }}" class="btn btn-primary">{{ __('Place order') }}</a>
                                </div>
                            @endforelse

                            @if($count > 5)
                                <a href="{{ route('meals.index') }}">{{ __('More orders') }}</a>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
