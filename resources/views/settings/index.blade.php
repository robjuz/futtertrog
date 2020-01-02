@extends('layouts.app')

@section('content')
    <h1>{{ __('Settings') }}</h1>

    @if($errors->count())
        <ul>
            @foreach($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('settings.store') }}">
        @csrf
        <section>
            <h2>{{ __('General') }}</h2>

            <label for="language">{{ __('Language') }}</label>
            <select name="language" id="language">
                @foreach(config('app.supported_locale') as $locale)
                    <option value="{{ $locale }}" {{ old('language', ($settings['language'] ?? app()->getLocale()) == $locale) ? 'selected' : '' }}>
                        @lang('futtertrog.locale.'. $locale)
                    </option>
                @endforeach
            </select>

            <a href="{{ route('meals.ical', ['api_token' => auth()->user()->api_token]) }}">{{ __('Download as iCal') }}</a>
        </section>

        <section>
            <h2>{{ __('Surprise me') }}</h2>

            <input type="hidden" name="hideDashboardMealDescription" value="0">
            <input type="checkbox"
                   name="hideDashboardMealDescription"
                   id="hideDashboardMealDescription"
                   {{ old('hideDashboardMealDescription', $settings['hideDashboardMealDescription'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="hideDashboardMealDescription">
                {{ __('Hide meal description on dashboard') }}
            </label>

            <input type="hidden" name="hideOrderingMealDescription" value="0">
            <input type="checkbox"
                   name="hideOrderingMealDescription"
                   id="hideOrderingMealDescription"
                   {{ old('hideOrderingMealDescription', $settings['hideOrderingMealDescription'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="hideOrderingMealDescription">
                {{ __('Hide meal description on ordering list') }}
            </label>
        </section>

        <section>
            <h2>{{ __('Notifications') }}</h2>

            <input type="hidden" name="newOrderPossibilityNotification" value="0">
            <input type="checkbox"
                   name="newOrderPossibilityNotification"
                   id="newOrderPossibilityNotification"
                   {{ old('newOrderPossibilityNotification', $settings['newOrderPossibilityNotification'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="newOrderPossibilityNotification">
                {{ __('New order possibility notification') }}
            </label>

            <input type="hidden" name="noOrderNotification" value="0">
            <input type="checkbox"
                   name="noOrderNotification"
                   id="noOrderNotification"
                   {{ old('noOrderNotification', $settings['noOrderNotification'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="noOrderNotification">
                <span>
                    {{ __('No order for today notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock.") }}
                </small>
            </label>

            <input type="hidden" name="noOrderForNextDayNotification" value="0">
            <input type="checkbox"
                   name="noOrderForNextDayNotification"
                   id="noOrderForNextDayNotification"
                   {{ old('noOrderForNextDayNotification', $settings['noOrderForNextDayNotification'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="noOrderForNextDayNotification">
                <span>
                    {{ __('No order for next day notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock.") }}
                </small>
            </label>

            <input type="hidden" name="noOrderForNextWeekNotification" value="0">
            <input type="checkbox"
                   name="noOrderForNextWeekNotification"
                   id="{{ \App\User::SETTING_NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}"
                   {{ old('noOrderForNextDayNotification', $settings[\App\User::SETTING_NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\User::SETTING_NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}">
                <span>
                    {{ __('No order for next week notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock on Thursday and Friday") }}
                </small>
            </label>
        </section>

        <section>
            <h2>{{ __('Ingredients') }}</h2>

            <label for="mealPreferences">
                <span>
                    {{ __('Meal preferences') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <textarea
                   name="mealPreferences"
                   id="mealPreferences"
            >{{ old('mealPreferences', $settings['mealPreferences'] ?? "") }}</textarea>

            <label for="mealAversion">
                <span>
                    {{ __('Meal excludes') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <textarea
                   name="mealAversion"
                   id="mealAversion"
            >{{ old('mealAversion', $settings['mealAversion'] ?? "") }}</textarea>
        </section>

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection
