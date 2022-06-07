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


            <h2>
                {{ __('Actions') }}
            </h2>

            @if($noOrderNotification)
                <form action="{{ route('notification.disable') }}" method="post">
                    @csrf
                    <input type="hidden" name="date" value="{{ request('date', today()->toDateString()) }}">
                    @if ($notificationEnabledThisDay)
                        <button type="submit">{{ __('Disable No order for today notification') }}</button>
                    @else
                        @method('delete')
                        <button type="submit">{{ __('Enable No order for today notification') }}</button>
                    @endif
                </form>
            @endif
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
