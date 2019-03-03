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
        <div class="d-none d-sm-block weekday week-of-year">{{ __('calendar.WN') }}</div>
        <div class="weekday">{{ __('calendar.Mo') }}</div>
        <div class="weekday">{{ __('calendar.Tu') }}</div>
        <div class="weekday">{{ __('calendar.We') }}</div>
        <div class="weekday">{{ __('calendar.Th') }}</div>
        <div class="weekday">{{ __('calendar.Fr') }}</div>
        <div class="weekday">{{ __('calendar.Sa') }}</div>
        <div class="weekday">{{ __('calendar.Su') }}</div>
    </div>
    @php
        $date = \Illuminate\Support\Carbon::parse($requestedDate)->startOfMonth();
        $daysInMonth = $date->daysInMonth;
    @endphp

    <div class="row no-gutters days">

        {{-- First day of isn't monday, add empty preceding column(s)--}}
        @if ($date->format('N') != 1)
            <div class="d-none d-sm-flex day week-of-year">{{ $date->weekOfYear }}</div>
            @for($i = 1; $i < $date->format('N'); $i++)
                <div class="day"></div>
            @endfor
        @endif

		@php $row = 0; @endphp
        @for($i = 1; $i <= $daysInMonth; $i++)
            @if ($date->format('N') == 1)
				@php $row++; @endphp
                <div class="w-100"></div>
                <div class="d-none d-sm-flex day week-of-year">{{ $date->weekOfYear }}</div>
            @endif

            <div class="day {{ $date->isSameDay($requestedDate) ? ' active' : '' }} {{ $orders->get($date->toDateString()) ? ' has-orders' : '' }}">
                <div class="inner">
                    @if ( ($meals->get($date->toDateString())))
						@if(!$orders->get($date->toDateString()) && $date > (new \DateTime))
							<div class="meals-available {{ strtolower($date->format('l')) }} row-{{$row}}">
								<svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="bell" class="svg-inline--fa fa-bell fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM67.53 368c21.22-27.97 44.42-74.33 44.53-159.42 0-.2-.06-.38-.06-.58 0-61.86 50.14-112 112-112s112 50.14 112 112c0 .2-.06.38-.06.58.11 85.1 23.31 131.46 44.53 159.42H67.53zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z"></path></svg>
							</div>
						@endif	
						
						@if($orders->get($date->toDateString()))
							<div class="food-container {{ strtolower($date->format('l')) }} row-{{$row}}">
								<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="utensils" class="svg-inline--fa fa-utensils fa-w-13" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 416 512"><path fill="currentColor" d="M207.9 15.2c.8 4.7 16.1 94.5 16.1 128.8 0 52.3-27.8 89.6-68.9 104.6L168 486.7c.7 13.7-10.2 25.3-24 25.3H80c-13.7 0-24.7-11.5-24-25.3l12.9-238.1C27.7 233.6 0 196.2 0 144 0 109.6 15.3 19.9 16.1 15.2 19.3-5.1 61.4-5.4 64 16.3v141.2c1.3 3.4 15.1 3.2 16 0 1.4-25.3 7.9-139.2 8-141.8 3.3-20.8 44.7-20.8 47.9 0 .2 2.7 6.6 116.5 8 141.8.9 3.2 14.8 3.4 16 0V16.3c2.6-21.6 44.8-21.4 48-1.1zm119.2 285.7l-15 185.1c-1.2 14 9.9 26 23.9 26h56c13.3 0 24-10.7 24-24V24c0-13.2-10.7-24-24-24-82.5 0-221.4 178.5-64.9 300.9z"></path></svg>
							</div>
						@endif
						
                        <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>" class="{{ strtolower($date->format('l')) }} row-{{$row}}">
                            <span>{{ $date->day }}</span>
                        </a>
						@if(!$orders->get($date->toDateString()) && $date > (new \DateTime))
							<div class="available-meals-tooltip">
								<div class="available-meals-tooltip-title">
									Es gibt <b>17</b> Bestellmöglichkeiten für <span class="text-primary">{{ $date->format('d.m.Y') }}</span>.
								</div>
							</div>
						@endif
                        @if($orders->get($date->toDateString()))                        
							<div class="ordered-meals-tooltip">
								<div class="ordered-meals-tooltip-title">
									Deine Bestellung für <span class="text-primary">{{ $date->format('d.m.Y') }}</span>:
								</div>
								<ol class="text-left my-0 list-unstyled">
									@foreach($orders->get($date->toDateString(), []) as $order)
									<li class="mt-2">{{ $order }}</li>
									@endforeach
								</ol>
							</div>
						@endif
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
