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

            <label for="{{ \App\UserSettings::LANGUAGE }}">{{ __('Language') }}</label>
            <select name="{{ \App\UserSettings::LANGUAGE }}" id="{{ \App\UserSettings::LANGUAGE }}">
                @foreach(config('app.supported_locale') as $locale)
                    <option value="{{ $locale }}" {{ old(\App\UserSettings::LANGUAGE, ($settings->language ?? app()->getLocale())) == $locale ? 'selected' : '' }}>
                        @lang('futtertrog.locale.'. $locale)
                    </option>
                @endforeach
            </select>

            <a href="{{ route('meals.ical', ['api_token' => auth()->user()->api_token]) }}">{{ __('Download as iCal') }}</a>
        </section>

        <section>
            <h2>{{ __('Surprise me') }}</h2>

            <input type="hidden" name="{{ \App\UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION }}"
                   id="{{ \App\UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION }}"
                   {{ old(\App\UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION, $settings->hideDashboardMealDescription) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::HIDE_DASHBOARD_MEAL_DESCRIPTION }}">
                {{ __('Hide meal description on dashboard') }}
            </label>

            <input type="hidden" name="{{ \App\UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION }}"
                   id="{{ \App\UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION }}"
                   {{ old(\App\UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION, $settings->hideOrderingMealDescription) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::HIDE_ORDERING_MEAL_DESCRIPTION }}">
                {{ __('Hide meal description on ordering list') }}
            </label>
        </section>

        <section>
            <h2>{{ __('Notifications') }}</h2>

            <input type="hidden" name="{{ \App\UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION }}"
                   id="{{ \App\UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION }}"
                   {{ old(\App\UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION, $settings->newOrderPossibilityNotification) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::NEW_ORDER_POSSIBILITY_NOTIFICATION }}">
                {{ __('New order possibility notification') }}
            </label>

            <input type="hidden" name="{{ \App\UserSettings::NO_ORDER_NOTIFICATION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::NO_ORDER_NOTIFICATION }}"
                   id="{{ \App\UserSettings::NO_ORDER_NOTIFICATION }}"
                   {{ old(\App\UserSettings::NO_ORDER_NOTIFICATION, $settings->noOrderNotification) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::NO_ORDER_NOTIFICATION }}">
                <span>
                    {{ __('No order for today notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock.") }}
                </small>
            </label>

            <input type="hidden" name="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION }}"
                   id="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION }}"
                   {{ old(\App\UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION, $settings->noOrderForNextDayNotification) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_DAY_NOTIFICATION }}">
                <span>
                    {{ __('No order for next day notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock.") }}
                </small>
            </label>

            <input type="hidden" name="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}" value="0">
            <input type="checkbox"
                   name="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}"
                   id="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}"
                   {{ old(\App\UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION, $settings->noOrderForNextDayNotification) ? 'checked' : '' }}
                   value="1"
            >
            <label for="{{ \App\UserSettings::NO_ORDER_FOR_NEXT_WEEK_NOTIFICATION }}">
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

            <label for="{{ \App\UserSettings::MEAL_PREFERENCES }}">
                <span>
                    {{ __('Meal preferences') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <textarea
                   name="{{ \App\UserSettings::MEAL_PREFERENCES }}"
                   id="{{ \App\UserSettings::MEAL_PREFERENCES }}"
            >{{ old(\App\UserSettings::MEAL_PREFERENCES, $settings->mealPreferences) }}</textarea>

            <label for="{{ \App\UserSettings::MEAL_AVERSION }}">
                <span>
                    {{ __('Meal excludes') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <textarea
                   name="{{ \App\UserSettings::MEAL_AVERSION }}"
                   id="{{ \App\UserSettings::MEAL_AVERSION }}"
            >{{ old(\App\UserSettings::MEAL_AVERSION, $settings->mealAversion) }}</textarea>
        </section>

        <button type="submit">{{ __('Save') }}</button>
    </form>
@endsection
