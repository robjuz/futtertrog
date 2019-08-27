@extends('layouts.app')

@inject('orders', 'App\Repositories\OrdersRepository')
@inject('meals', 'App\Repositories\MealsRepository')

@section('content')
    <h1>@lang('Order meal for :date', ['date' => $requestedDate->format(trans('futtertrog.date_format'))])</h1>

    <a class="skip-link skip-calendar sr-only" href="#current-offer">
        {{ __('Skip calendar') }}
    </a>

    <nav id="calendar">
        <a href="<?= route('meals.index', ['date' => $previousMonth->toDateString()]) ?>">
            <span aria-hidden="true">&larr;</span>
            Bestellübersicht für {{ __('calendar.' . $previousMonth->format('F')) }} {{ $previousMonth->format('Y') }}
        </a>

        @php
            $date = $requestedDate->clone()->startOfMonth();
            $daysInMonth = $date->daysInMonth;
        @endphp

        <ol>
            @for($i = 1; $i <= $date->daysInMonth; $i++)
                <li
                    class="
                        @if($date->isWeekend())
                                    weekend
                        @endif

                        @if($date->isToday())
                                    today
                        @endif

                        @if($date->isSameDay($requestedDate))
                                    selected
                        @endif
                        "
                >

                    <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                        <span class="weekday">{{ $date->format('l') }}</span>
                        <span class="day">{{ $date->day }}</span>
                        <span class="month">{{ $date->format('F') }}</span>


                        @if($orders->userOrdersForDate($date)->isNotEmpty())
                            <ol class="ordered">
                                @foreach($orders->userOrdersForDate($date) as $order)
                                    <li>
                                        <svg role="presentation">
                                            <path d="M5,10 h20 v8 a4,4 0,0,1, -4,4 h-12 a4,4 0,0,1, -4,-4 v-8 z"></path>
                                            <path d="M3,7 h11 a2,2 0,1,1 2,0 h11 v1 a1,1 0,0,1, -1,1 h-22 a1,1 0,0,1, -1,-1 v-1 z"></path>
                                        </svg>
                                    </li>
                                @endforeach
                            </ol>
                        @elseif($meals->forDate($date)->count())
                            <span class="order">{{ $meals->forDate($date)->count() }} Bestellmöglichkeiten</span>
                        @endif

                    </a>
                </li>

                @php
                    $date->addDay();
                @endphp
            @endfor
        </ol>

        <a href="<?= route('meals.index', ['date' => $nextMonth->toDateString()]) ?>">
            Bestellübersicht für {{ __('calendar.' . $nextMonth->format('F')) }} {{ $nextMonth->format('Y') }}
            <span aria-hidden="true">&rarr;</span>
        </a>
    </nav>

    <section id="current-offer" <?php /* keep id for skip link */ ?>>

        @if(!empty($todayMeals))
            <ol>
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

            <a class="text-right" href="#current-offer">
                Zurück zum Anfang der Liste
            </a>
        @else
            <p>
                {{ __('No items found') }}
            </p>
        @endif

    </section>
@endsection
