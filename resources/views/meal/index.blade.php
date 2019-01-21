@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }} für <span class="text-primary">{{ $requestedDate->format('d.m.Y') }}</span> </h2>

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
                                {{ $requestedDate->format('F') }}&nbsp;
                                {{ $requestedDate->format('Y') }}
                            </li>
                        </ul>
                    </div>

                    <div class="row no-gutters weekdays">
                        <div class="col weekday week-of-year">{{ __('calendar.WN') }}</div>
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
                            <div class="col day week-of-year">{{ $date->weekOfYear }}</div>
                            @for($i = 1; $i < $date->format('N'); $i++)
                                <div class="col day"></div>
                            @endfor
                        @endif

                        @for($i = 1; $i <= $daysInMonth; $i++)
                            @if ($date->format('N') == 1)
                                <div class="w-100"></div>
                                <div class="col day week-of-year">{{ $date->weekOfYear }}</div>
                            @endif

                            <div class="position-relative col day {{ $date->isSameDay($requestedDate) ? ' active' : '' }} {{ $orders->where('date', $date)->count() ? ' has-orders' : '' }}">
                                <div class="inner">
                                @if ($meals->where('date', $date)->count())
                                    <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                                        <span>{{ $date->day }}</span>
                                    </a>
                                    <div class="food-container">
                                        @if($ordersCount = $orders->where('date', $date)->sum(function($order) { return $order->pivot->quantity;}))
                                            @foreach($orders->where('date', $date) as $order)
                                                <span title="{{$order->title}}">
                                                    <svg aria-hidden="true" data-prefix="fas" data-icon="drumstick-bite" class="svg-inline--fa fa-drumstick-bite fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M462.79 49.57c-66.14-66.09-173.36-66.09-239.5 0C187.81 85.02 160.12 128 160.12 192v85.83l-40.62 40.59c-9.7 9.69-24.04 11.07-36.78 5.98-21.72-8.68-47.42-4.29-65.02 13.29-23.61 23.59-23.61 61.84 0 85.43 15.28 15.27 36.53 19.58 56.14 15.09-4.5 19.6-.18 40.83 15.1 56.1 23.61 23.59 61.88 23.59 85.49 0 17.6-17.58 21.99-43.26 13.31-64.97-5.09-12.73-3.72-27.05 5.99-36.75L234.35 352h85.89c23.2 0 43.57-3.72 61.89-10.03-39.64-43.89-39.83-110.23 1.05-151.07 34.38-34.36 86.76-39.46 128.74-16.8 1.3-44.93-14.81-90.25-49.13-124.53z"></path></svg>
                                                </span>
                                            @endforeach
                                        @endif
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
