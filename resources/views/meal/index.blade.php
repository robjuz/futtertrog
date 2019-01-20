@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }}</h2>

        <div class="row justify-content-center">
            <div class="col-md-auto">

                <div id="calendar">
                    <div class="month">
                        <ul>
                            <li class="prev">
                                <a href="<?= route('meals.index',
                                    ['date' => \Carbon::parse($requestedDate)->subMonthNoOverflow()->toDateString()]) ?>">&#10094;</a>
                            </li>
                            <li class="next">
                                <a href="<?= route('meals.index',
                                    ['date' => \Carbon::parse($requestedDate)->addMonthNoOverflow()->toDateString()]) ?>">&#10095;</a>
                            </li>
                            <li>
                                {{ $requestedDate->format('F') }}<br>
                                {{ $requestedDate->format('Y') }}
                            </li>
                        </ul>
                    </div>

                    <div class="row no-gutters weekdays">
                        <div class="col weekday">{{ __('calendar.WN') }}</div>
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
                            <div class="col day">{{ $date->weekOfYear }}</div>
                            @for($i = 1; $i < $date->format('N'); $i++)
                                <div class="col day"></div>
                            @endfor
                        @endif

                        @for($i = 1; $i <= $daysInMonth; $i++)
                            @if ($date->format('N') == 1)
                                <div class="w-100"></div>
                                <div class="col day">{{ $date->weekOfYear }}</div>
                            @endif
                            <div class="position-relative col day {{ $date->isSameDay($requestedDate) ? ' active' : '' }} {{ $orders->where('date', $date)->count() ? ' has-orders' : '' }}">
                                @if ($meals->where('date', $date)->count())
                                <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                                    {{ $date->day }}
                                </a>
                                @else
                                    {{ $date->day }}
                                @endif
                                @if($count = $orders->where('date', $date)->sum(function($order) { return $order->pivot->quantity;}))
                                    <div class="position-absolute top-right">{{ $count }}</div>
                                @endif

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
            </div>

            <div class="col">

                @foreach($messages as $message)
                    <div class="alert alert-{{$message['type']}}" role="alert">
                        {{ $message['text'] }}
                    </div>
                @endforeach

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

                @foreach($meals->where('date', $requestedDate) as $meal)
                    <div class="meal-container">
                        @include('meal.meal')
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endsection
