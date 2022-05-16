<scroll-into-view>
    <nav id="calendar">
        <header>
            <h1>@lang('Order meal for :date', ['date' => trans('calendar.'. $requestedDate->englishDayOfWeek) . ' ' . $requestedDate->format(trans('futtertrog.date_format'))])</h1>

            <a href="<?= route('meals.index', ['date' => $previousWeek->toDateString()]) ?>">
                <span aria-hidden="true">&larr;</span>
                {{ __('calendar.WN') }} {{ $previousWeek->weekOfYear }}
            </a>

            @if($nextWeek)
            <a href="<?= route('meals.index', ['date' => $nextWeek->toDateString()]) ?>">
                {{ __('calendar.WN') }} {{ $nextWeek->weekOfYear }}
                <span aria-hidden="true">&rarr;</span>
            </a>
            @endif

        </header>
        <ol>
            @foreach($period as $date)
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
