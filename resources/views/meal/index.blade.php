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
               @include('meal.calendar')
            </div>

            <section role="region" id="current-offer" class="col" aria-label="Bestellmöglichkeiten für {{ $requestedDate->format(trans('futtertrog.date_format')) }}">
				@php
					$date = \Illuminate\Support\Carbon::parse($requestedDate);
				@endphp
				
				<div class="row mb-2">
					<div class="col">
						<a href="<?= route('meals.index', ['date' => $date->addDay(-1)->toDateString()]) ?>">
							<span aria-hidden="true">&lt;</span> Vorheriger Tag
						</a>
					</div>
					<div class="col-auto">
						<a href="<?= route('meals.index', ['date' => $date->addDay(2)->toDateString()]) ?>">
							Nächster Tag <span aria-hidden="true">&gt;</span>
						</a>
					</div>
				</div>

				@if(!empty($todayMeals))
					<input type="radio" id="list" name="radio-group" class="meals-display-option" checked>
					<label for="list" class="meals-list-option">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="list" class="svg-inline--fa fa-list fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M128 116V76c0-8.837 7.163-16 16-16h352c8.837 0 16 7.163 16 16v40c0 8.837-7.163 16-16 16H144c-8.837 0-16-7.163-16-16zm16 176h352c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H144c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h352c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H144c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zM16 144h64c8.837 0 16-7.163 16-16V64c0-8.837-7.163-16-16-16H16C7.163 48 0 55.163 0 64v64c0 8.837 7.163 16 16 16zm0 160h64c8.837 0 16-7.163 16-16v-64c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v64c0 8.837 7.163 16 16 16zm0 160h64c8.837 0 16-7.163 16-16v-64c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v64c0 8.837 7.163 16 16 16z"></path></svg>
					</label>

					<input type="radio" id="two-columns" name="radio-group" class="meals-display-option">
					<label for="two-columns" class="meals-two-columns-option">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="th-large" class="svg-inline--fa fa-th-large fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M296 32h192c13.255 0 24 10.745 24 24v160c0 13.255-10.745 24-24 24H296c-13.255 0-24-10.745-24-24V56c0-13.255 10.745-24 24-24zm-80 0H24C10.745 32 0 42.745 0 56v160c0 13.255 10.745 24 24 24h192c13.255 0 24-10.745 24-24V56c0-13.255-10.745-24-24-24zM0 296v160c0 13.255 10.745 24 24 24h192c13.255 0 24-10.745 24-24V296c0-13.255-10.745-24-24-24H24c-13.255 0-24 10.745-24 24zm296 184h192c13.255 0 24-10.745 24-24V296c0-13.255-10.745-24-24-24H296c-13.255 0-24 10.745-24 24v160c0 13.255 10.745 24 24 24z"></path></svg>
					</label>

					<input type="radio" id="grid" name="radio-group" class="meals-display-option">
					<label for="grid" class="meals-grid-option">
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="th" class="svg-inline--fa fa-th fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M149.333 56v80c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V56c0-13.255 10.745-24 24-24h101.333c13.255 0 24 10.745 24 24zm181.334 240v-80c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.256 0 24.001-10.745 24.001-24zm32-240v80c0 13.255 10.745 24 24 24H488c13.255 0 24-10.745 24-24V56c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24zm-32 80V56c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.256 0 24.001-10.745 24.001-24zm-205.334 56H24c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24zM0 376v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H24c-13.255 0-24 10.745-24 24zm386.667-56H488c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24zm0 160H488c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H386.667c-13.255 0-24 10.745-24 24v80c0 13.255 10.745 24 24 24zM181.333 376v80c0 13.255 10.745 24 24 24h101.333c13.255 0 24-10.745 24-24v-80c0-13.255-10.745-24-24-24H205.333c-13.255 0-24 10.745-24 24z"></path></svg>
					</label>


					<ol class="list-unstyled row flex-wrap">
						@foreach($todayMeals as $meal)
							<li id="meal_{{ $meal->id }}" class="col meal-container">
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

            </section>
        </div>
    </div>
@endsection
