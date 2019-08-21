@inject('meals', 'App\Repositories\MealsRepository')
@inject('orders', 'App\Repositories\OrdersRepository')


<a class="skip-link skip-calendar sr-only" href="#current-offer">
    {{ __('Skip calendar') }}
</a>

<nav id="calendar">
         <a href="<?= route(
                'meals.index',
                [
                    'date' => \Carbon::parse($requestedDate)
                        ->subMonthNoOverflow()
                        ->lastOfMonth()
                        ->toDateString(),
                ]
            ) ?>"
         >
             <span aria-hidden="true">&larr;</span>
             Bestellübersicht für {{ __('calendar.' . \Carbon::parse($requestedDate)->subMonthNoOverflow()->format('F')) }} {{ $requestedDate->format('Y') }}
         </a>

    @php
        $date = \Illuminate\Support\Carbon::parse($requestedDate)->startOfMonth();
        $daysInMonth = $date->daysInMonth;
    @endphp

    <ol>
        @for($i = 1; $i <= $daysInMonth; $i++)
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
                                    <svg viewbox="0 0 30 26">
                                        <path
                                            d="M5,10
                                               h20
                                               v8
                                               a4,4 0,0,1, -4,4
                                               h-12
                                               a4,4 0,0,1, -4,-4
                                               v-8
                                               z"></path>
                                        <path d="
                                               M3,7
                                               h11
                                               a2,2 0,1,1 2,0
                                               h11
                                               v1
                                               a1,1 0,0,1, -1,1
                                               h-22
                                               a1,1 0,0,1, -1,-1
                                               v-1
                                               z"></path>
                                    </svg>
                                </li>
                            @endforeach
                        </ol>
                    @elseif($meals->forDate($date)->count())
                        <span class="order">B</span>
                    @endif

                </a>
            </li>

            @php
                $date->addDay();
            @endphp
        @endfor
    </ol>

    <a href="<?= route(
        'meals.index',
        [
        'date' => \Carbon::parse($requestedDate)
        ->addMonthNoOverflow()
        ->firstOfMonth()
        ->toDateString(),
        ]
        ) ?>"
    >
        Bestellübersicht für {{ __('calendar.' . \Carbon::parse($requestedDate)->addMonthNoOverflow()->format('F')) }} {{ $requestedDate->format('Y') }}
        <span aria-hidden="true">&rarr;</span>
    </a>

</nav>
