@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <h2 class="py-3">{{ __('Order meal') }}</h2>

        <div class="row">
            <div class="col-md-4">

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
                            <div class="col day {{ $date->isSameDay($requestedDate) ? ' active' : '' }} {{ $orders->where('date', $date)->count() ? ' has-orders' : '' }}">
                                <a href="<?= route('meals.index', ['date' => $date->toDateString()]) ?>">
                                    {{ $date->day }}
                                </a>

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

            <div class="col-md-8 col-lg-6">

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
                        </div>
                    </div>
                </form>

                @foreach($meals as $meal)

                    <div class="border-top border-bottom py-3">
                        @can('update', $meal)
                            <a href="{{ route('meals.edit', $meal) }}" class="btn btn-link text-info pl-0">
                                {{ __('Edit') }}
                            </a>
                        @endcan

                        @can('delete', $meal)
                            <form action="{{ route('meals.destroy', $meal) }}" method="post" class="d-inline-block">
                                @method('delete')
                                @csrf
                                <button type="submit" class="btn btn-link text-danger">{{ __('Delete') }}</button>
                            </form>
                        @endcan

                        <h4 class="d-flex justify-content-between">
                            {{ $meal->title }}
                            <div>
                                <small>{{ number_format($meal->price, 2, ',', '.') }} €</small>

                                @can('order', $meal)
                                    <form action="{{ route('user_meal', $meal) }}" method="post" class="d-inline-block">
                                        @csrf

                                        @if($orders->contains($meal))
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                {{ __('Delete order') }}
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                {{ __('Place order') }}
                                            </button>
                                        @endif
                                    </form>
                                @endcan
                            </div>
                        </h4>

                        <p class="text-dark">{{ $meal->description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
