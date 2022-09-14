<scroll-into-view>
    <nav id="calendar" class="week-navigation">
        <header class="week-navigation__header">
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
        <ol class="week-navigation__list">
            @foreach($period as $date)
                <li class="week-navigation__list-item {{ $listItemClasses($date) }}">
                    @if ($meals->forDate($date)->isEmpty())
                        <div>
                    @else
                        <a href="{{ route('meals.index', ['date' => $date->toDateString()]) }}">
                    @endif

                    @if($notificationDisabled($date))
                        @svg('solid/bell-slash', ['role=presentation', 'aria-hidden=true', 'focusable=false', 'class=week-navigation__icon--notification-disabled'])
                    @endif

                    <span class="weekday">{{ @trans('calendar.'.$date->format('l')) }}</span>
                    <span class="day">{{ $date->day }}</span>

                    @if(auth()->user()->orderItems()->date($date)->positive()->exists())
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
