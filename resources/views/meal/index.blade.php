@extends('layouts.app')

@inject('orders', 'App\Repositories\OrdersRepository')
@inject('meals', 'App\Repositories\MealsRepository')

@section('before')
    <scroll-into-view>
        <nav id="calendar">
            <header>
                <a href="<?= route('meals.index', ['date' => $previousMonth->toDateString()]) ?>">
                    <span aria-hidden="true">&larr;</span>
                    {{ __('calendar.' . $previousMonth->format('F')) }} {{ $previousMonth->format('Y') }}
                </a>

                <h1>@lang('Order meal for :date', ['date' => trans('calendar.'. $requestedDate->englishDayOfWeek) . ' ' . $requestedDate->format(trans('futtertrog.date_format'))])</h1>

                <a href="<?= route('meals.index', ['date' => $nextMonth->toDateString()]) ?>">
                    {{ __('calendar.' . $nextMonth->format('F')) }} {{ $nextMonth->format('Y') }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </header>
            <ol>
                @foreach($month as $date)
                    <li class="{{ $date->isWeekend() ? ' weekend' : '' }}{{ $date->isToday() ? ' today' : '' }}{{ $date->isSameDay($requestedDate) ? ' selected' : '' }}">
                        @if ($meals->forDate($date)->isEmpty())
                            <div>
                                @else
                                    <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                                        @endif
                                        <span class="weekday">{{ @trans('calendar.'.$date->format('l')) }}</span>
                                        <span class="day">{{ $date->day }}</span>

                                        @if($orders->userOrdersForDate($date)->isNotEmpty())
                                            <p class="ordered">{{__('Ordered')}}</p>
                                        @elseif($meals->forDate($date)->count())
                                            <p class="order">{{ __(':count order possibilities', [ 'count' => $meals->forDate($date)->count()]) }}</p>
                                @endif
                                @if ($meals->forDate($date)->isEmpty())
                            </div>
                            @else
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </scroll-into-view>
@endsection

@section('content')



        <section id="current-offer" <?php /* keep id for skip link */ ?>>
            <aside class="filters">
                <h2>
                    {{ __('Filters') }}
                </h2>
                <form action="{{ route('meals.index') }}" method="get">
                    <input type="hidden" name="date" value="{{ request('date', today()->toDateString()) }}">

                    <label for="provider">{{ __("Provider") }}</label>
                    <select id="provider" name="provider">
                        <option>{{ __('All') }}</option>
                        @foreach(\App\Meal::$providers as $provider)
                            <option value="{{ $provider }}" {{ request('provider') == $provider ? ' selected' : '' }}>{{ $provider }}</option>
                        @endforeach
                    </select>

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
