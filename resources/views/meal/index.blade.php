@extends('layouts.app')

@inject('orders', 'App\Repositories\OrdersRepository')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }} für <span class="text-primary">{{ $requestedDate->format(trans('futtertrog.date_format')) }}</span></h2>

		<a class="skip-link skip-calendar" href="#current-offer">
			Kalender überspringen
		</a>

        <div class="row justify-content-center">
			
            <div class="col-xs-12 col-auto">
               @include('meals.calendar')
            </div>

            <div id="current-offer" class="col">
				@php
					$date = \Illuminate\Support\Carbon::parse($requestedDate);
				@endphp
				
				<div class="row mb-2">
					<div class="col">
						<a href="<?= route('meals.index', ['date' => $date->addDay(-1)->toDateString()]) ?>">
							&lt; Vorheriger Tag
						</a>
					</div>
					<div class="col-auto">
						<a href="<?= route('meals.index', ['date' => $date->addDay(2)->toDateString()]) ?>">
							Nächster Tag &gt;
						</a>
					</div>
				</div>

				@if(!empty($todayMeals))
					<ol class="list-unstyled">
						@foreach($todayMeals as $meal)
							<li id="meal_{{ $meal->id }}" class="meal-container">
								@include('meal.meal')
							</li>
						@endforeach
					</ol>
					<div>
						<a class="text-right" href="#current-offer">
							Zurück zum Anfang der Liste
						</a>
					</div>
                @else
                    <div class="alert alert-warning" role="alert">
                        <strong>{{ __('No items found') }}</strong>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
