@extends('layouts.app')

@section('content')
    @if($errors->count())
        <ul>
            @foreach($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    @endif



    <main>
        <h1>{{ __('Settings') }}</h1>

        <form class="settings-form" method="POST" action="{{ route('settings.store') }}">
            @csrf

            <h2>{{ __('General') }}</h2>

            <input type="checkbox"
                   name="darkMode"
                   id="darkMode"
                   {{ old('darkMode', $settings['darkMode'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="darkMode">
                {{ __('Dark mode') }}
            </label>

            <label for="language">{{ __('Language') }}</label>
            <select name="language" id="language">
                @foreach(config('app.supported_locale') as $locale)
                    <option value="{{ $locale }}" {{ old('language', ($settings['language'] ?? app()->getLocale()) == $locale) ? 'selected' : '' }}>
                        @lang('futtertrog.locale.'. $locale)
                    </option>
                @endforeach
            </select>

            <h2>{{ __('Surprise me') }}</h2>

            <input type="checkbox"
                   name="hideDashboardMealDescription"
                   id="hideDashboardMealDescription"
                   {{ old('hideDashboardMealDescription', $settings['hideDashboardMealDescription'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="hideDashboardMealDescription">
                {{ __('Hide meal description on dashboard') }}
            </label>

            <input type="checkbox"
                   name="hideOrderingMealDescription"
                   id="hideOrderingMealDescription"
                   {{ old('hideOrderingMealDescription', $settings['hideOrderingMealDescription'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="hideOrderingMealDescription">
                {{ __('Hide meal description on ordering list') }}
            </label>

            <h2>{{ __('Notifications') }}</h2>

            <input type="checkbox"
                   name="newOrderPossibilityNotification"
                   id="newOrderPossibilityNotification"
                   {{ old('newOrderPossibilityNotification', $settings['newOrderPossibilityNotification'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label for="newOrderPossibilityNotification">
                {{ __('New order possibility notification') }}
            </label>

            <input type="checkbox"
                   name="noOrderNotification"
                   id="noOrderNotification"
                   {{ old('noOrderNotification', $settings['noOrderNotification'] ?? false) ? 'checked' : '' }}
                   value="1"
            >
            <label>
                <span>
                    {{ __('No order for today notification') }}
                </span>
                <small>
                    {{ __("Will be sent at 10 o'clock.") }}
                </small>
            </label>

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

            <input type="checkbox"
                   name="noOrderForNextDayNotification"
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

            <h2>{{ __('Ingredients') }}</h2>

            <label for="mealPreferences">
                <span>
                    {{ __('Meal preferences') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <input type="text"
                   name="mealPreferences"
                   id="mealPreferences"
                   value="{{ old('mealPreferences', $settings['mealPreferences'] ?? null) }}"
            >

            <label for="mealAversion">
                <span>
                    {{ __('Meal excludes') }}
                </span>
                <small>
                    {{ __('Comma-separated values') }}
                </small>
            </label>
            <input type="text"
                   name="mealAversion"
                   id="mealAversion"
                   value="{{ old('mealAversion', $settings['mealAversion'] ?? null) }}"
            >

            <button type="submit">{{ __('Save') }}</button>
        </form>
    </main>
@endsection
