<div id="calendar">
    <div class="month">
        <ul>
            <li class="prev">
                <a href="<?= route('meals.index', ['date' => \Carbon::parse($requestedDate)->subMonthNoOverflow()->lastOfMonth()->toDateString()]) ?>">&#10094;</a>
            </li>
            <li class="current-month">
                {{ $requestedDate->format('F') }}&nbsp;
                {{ $requestedDate->format('Y') }}
            </li>
			<li class="next">
                <a href="<?= route('meals.index', ['date' => \Carbon::parse($requestedDate)->addMonthNoOverflow()->firstOfMonth()->toDateString()]) ?>">&#10095;</a>
            </li>
        </ul>
    </div>

    <div class="row no-gutters weekdays">
        <div class="d-none d-sm-block col weekday week-of-year">{{ __('calendar.WN') }}</div>
        <div class="col weekday">{{ __('calendar.Mo') }}</div>
        <div class="col weekday">{{ __('calendar.Tu') }}</div>
        <div class="col weekday">{{ __('calendar.We') }}</div>
        <div class="col weekday">{{ __('calendar.Th') }}</div>
        <div class="col weekday">{{ __('calendar.Fr') }}</div>
        <div class="col weekday">{{ __('calendar.Sa') }}</div>
        <div class="col weekday">{{ __('calendar.Su') }}</div>
    </div>
    @php
        $date = \Illuminate\Support\Carbon::parse($requestedDate)->startOfMonth();
        $daysInMonth = $date->daysInMonth;
    @endphp

    <div class="row no-gutters days">

        {{-- First day of isn't monday, add empty preceding column(s)--}}
        @if ($date->format('N') != 1)
            <div class="d-none d-sm-flex col day week-of-year">{{ $date->weekOfYear }}</div>
            @for($i = 1; $i < $date->format('N'); $i++)
                <div class="col day"></div>
            @endfor
        @endif

        @for($i = 1; $i <= $daysInMonth; $i++)
            @if ($date->format('N') == 1)
                <div class="w-100"></div>
                <div class="d-none d-sm-flex col day week-of-year">{{ $date->weekOfYear }}</div>
            @endif

            <div class="position-realtive col day {{ $date->isSameDay($requestedDate) ? ' active' : '' }} {{ $orders->get($date->toDateString()) ? ' has-orders' : '' }}">
                <div class="inner">
                    @if ( ($meals->get($date->toDateString())))
                        <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                            <span>{{ $date->day }}</span>
                        </a>
                        <div class="food-container">
                            @foreach($orders->get($date->toDateString(), []) as $order)
                                <span title="{{ $order }}">
                                    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="utensils" class="svg-inline--fa fa-utensils fa-w-13" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 416 512"><path fill="currentColor" d="M207.9 15.2c.8 4.7 16.1 94.5 16.1 128.8 0 52.3-27.8 89.6-68.9 104.6L168 486.7c.7 13.7-10.2 25.3-24 25.3H80c-13.7 0-24.7-11.5-24-25.3l12.9-238.1C27.7 233.6 0 196.2 0 144 0 109.6 15.3 19.9 16.1 15.2 19.3-5.1 61.4-5.4 64 16.3v141.2c1.3 3.4 15.1 3.2 16 0 1.4-25.3 7.9-139.2 8-141.8 3.3-20.8 44.7-20.8 47.9 0 .2 2.7 6.6 116.5 8 141.8.9 3.2 14.8 3.4 16 0V16.3c2.6-21.6 44.8-21.4 48-1.1zm119.2 285.7l-15 185.1c-1.2 14 9.9 26 23.9 26h56c13.3 0 24-10.7 24-24V24c0-13.2-10.7-24-24-24-82.5 0-221.4 178.5-64.9 300.9z"></path></svg>
								</span>
                            @endforeach
                        </div>
                    @else
                        <span class="a">{{ $date->day }}</span>
                    @endif
                </div>
            </div>
            @php
                $date->addDay();
            @endphp
        @endfor

        {{-- If we don't endet up on monday, append empty column(s)--}}
        @if ($date->format('N') != 1)
            @for($i = ($date->format('N')); $i <= 7; $i++)
                <div class="col day"></div>
            @endfor
        @endif
    </div>


</div>
