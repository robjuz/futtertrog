@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }} für <span
                    class="text-primary">{{ $requestedDate->format('d.m.Y') }}</span></h2>

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
               @include('meals.calendar')
            </div>

            <div class="col">

                <form action="{{ route('meals.index') }}" method="GET">

                    <input type="hidden" name="date" value="{{ $requestedDate->format('Y-m-d') }}">

                    <div class="form-row align-items-end">
                        <div class="form-group col">
                            <label for="includes">{{  __('includes') }}</label>
                            <input type="text"
                                   name="includes"
                                   id="includes"
                                   class="form-control"
                                   value="{{ $includes }}"
                            >
                        </div>

                        <div class="form-group col">
                            <label for="excludes">{{  __('excludes') }}</label>
                            <input type="text"
                                   name="excludes"
                                   id="excludes"
                                   class="form-control"
                                   value="{{ $excludes }}"
                            >
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-secondary">{{ __('Go') }}</button>
                            <input type="submit" class="btn btn-dark" name="reset" value="{{ __('Reset') }}">
                        </div>
                    </div>
                </form>

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
