@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }} für <span class="text-primary">{{ $requestedDate->format(trans('futtertrog.date_format')) }}</span></h2>

        <div class="row justify-content-center">
            <div class="col-lg-5 col-xl-4">
               @include('meals.calendar')
            </div>

            <div class="col">
                @forelse($todayMeals as $meal)
                    <div id="meal_{{ $meal->id }}" class="meal-container">
                        @include('meal.meal')
                    </div>
                @empty
                    <div class="alert alert-warning" role="alert">
                        <strong>{{ __('No items found') }}</strong>
                    </div>
                @endforelse

            </div>
        </div>
    </div>
@endsection
