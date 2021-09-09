@extends('layouts.app')
@inject('providers', 'mealProviders')

@section('before')
    <x-week-navigation/>
@endsection

@section('content')
    <section id="current-offer" <?php /* keep id for skip link */ ?>>
        <aside class="filters">
            <h2>
                {{ __('Filters') }}
            </h2>
            <form action="{{ route('meals.index') }}" method="get">
                <input type="hidden" name="date" value="{{ request('date', today()->toDateString()) }}">

                <x-provider-select/>

                <button type="submit">{{ __('Search') }}</button>
            </form>
        </aside>
        @if(($todayMeals)->isNotEmpty())
            <ol class="tiles">
                @foreach($todayMeals as $meal)
                    <li id="meal_{{ $meal->id }}"
                        @if($todayOrders->firstWhere('meal_id', $meal->id))
                        class="selected"
                        @endif
                    >
                        @include('meal.meal')
                    </li>
                @endforeach
            </ol>
        @else
            <p>
                {{ __('No items found') }}
            </p>
        @endif
    </section>
@endsection
