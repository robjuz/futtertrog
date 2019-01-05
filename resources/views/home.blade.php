@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-6 mb-3">

                <div class="card mb-3">
                    <div class="card-header">{{ __('Your balance') }}</div>

                    <div class="card-body">
                        <span class="{{ $balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    @include('deposit_history')
                </div>

                @include('order_history')

            </div>

            <div class="col-md-6 mb-3">
                <div class="card mb-3">
                    <div class="card-header">{{ __('Your today order') }}</div>

                    <div class="card-body">
                        @if($todayMeals->count())

                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">{{ __('Title') }}</th>
                                    <th scope="col">{{ __('Description') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($todayMeals as $meal)
                                    <tr>
                                        <th scope="row" class="text-nowrap">{{ $meal->title }}</th>
                                        <td>{{ $meal->description }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="card bg-danger">

                                <div class="card-body py-1">
                                    {{ __('No orders for today') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">{{ __('Your upcoming orders') }}</div>

                    <div class="card-body">
                        <div class="card-deck flex-column">
                            @if($futureMeals->count())
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">{{ __('Date') }}</th>
                                        <th scope="col">{{ __('Order') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($futureMeals->groupBy(function($meal) { return $meal->date->format(__('futtertrog.d.m.Y')); }) as $date => $meals)

                                        @foreach($meals as $meal)
                                            <tr>
                                                @if ($loop->iteration == 1)
                                                    <th scope="row" rowspan="{{ $meals->count() }}">
                                                        {{ $date }}
                                                    </th>
                                                @endif
                                                <td>
                                                    <strong>{{ $meal->title }}</strong>
                                                    <div title="{{ $meal->description }}">
                                                        {{ $meal->description }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>

                                {{ $futureMeals->links() }}
                            @else
                                <div class="card bg-danger">

                                    <div class="card-body py-1">
                                        {{ __('No upcoming orders') }}
                                    </div>
                                </div>
                                <div class="card py-2 mt-2">
                                    <a href="{{ route('meals.index') }}"
                                       class="btn btn-primary">{{ __('Place order') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
