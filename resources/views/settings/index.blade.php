@extends('layouts.app')

@section('content')
    @if($errors->count())
        <div class="container">
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $error)
                    <div class="my-3">
                        <strong>{{ $error }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    @endif



    <div class="container">

        <h2>{{ __('Settings') }}</h2>

        <form method="POST" action="{{ route('settings.store') }}">
            @csrf

            <div class="row">

                <section class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">{{ __('General') }}</div>
                        <div class="card-body">

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="darkMode" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="darkMode"
                                           id="darkMode"
                                           {{ old('darkMode', $settings['darkMode'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="darkMode">
                                        {{ __('Dark mode') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="language">{{ __('Language') }}</label>
                                <select class="custom-select" name="language" id="language">
                                    @foreach(config('app.supported_locale') as $locale)
                                        <option value="{{ $locale }}" {{ old('language', ($settings['language'] ?? app()->getLocale()) == $locale) ? 'selected' : '' }}>@lang('futtertrog.locale.'. $locale)</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </div>
                </section>

                <section class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">{{ __('Surprise me') }}</div>
                        <div class="card-body">

                            <div class="form-group pb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="hideDashboardMealDescription" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="hideDashboardMealDescription"
                                           id="hideDashboardMealDescription"
                                           {{ old('hideDashboardMealDescription', $settings['hideDashboardMealDescription'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="hideDashboardMealDescription">
                                        {{ __('Hide meal description on dashboard') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group pb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="hideOrderingMealDescription" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="hideOrderingMealDescription"
                                           id="hideOrderingMealDescription"
                                           {{ old('hideOrderingMealDescription', $settings['hideOrderingMealDescription'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="hideOrderingMealDescription">
                                        {{ __('Hide meal description on ordering list') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">{{ __('Notifications') }}</div>
                        <div class="card-body">

                            <div class="form-group pb-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="newOrderPossibilityNotification" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="newOrderPossibilityNotification"
                                           id="newOrderPossibilityNotification"
                                           {{ old('newOrderPossibilityNotification', $settings['newOrderPossibilityNotification'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="newOrderPossibilityNotification">
                                        {{ __('New order possibility notification') }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group pb-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="noOrderNotification" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="noOrderNotification"
                                           id="noOrderNotification"
                                           {{ old('noOrderNotification', $settings['noOrderNotification'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                           aria-describedby="noOrderNotificationHelp"
                                    >
                                    <label class="custom-control-label" for="noOrderNotification">
                                        {{ __('No order for today notification') }}
                                    </label>
                                    <small id="noOrderNotificationHelp" class="form-text text-muted">
                                        {{ __("Will be sent at 10 o'clock.") }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-group pb-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="noOrderForNextDayNotification" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="noOrderForNextDayNotification"
                                           id="noOrderForNextDayNotification"
                                           {{ old('noOrderForNextDayNotification', $settings['noOrderForNextDayNotification'] ?? false) ? 'checked' : '' }}
                                           value="1"
                                           aria-describedby="noOrderForNextDayNotificationHelp"
                                    >
                                    <label class="custom-control-label" for="noOrderForNextDayNotification">
                                        {{ __('No order for next day notification') }}
                                    </label>
                                    <small id="noOrderForNextDayNotificationHelp" class="form-text text-muted">
                                        {{ __("Will be sent at 10 o'clock.") }}
                                    </small>
                                </div>
                            </div>

                        </div>
                    </div>
                </section>

                <section class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">{{ __('Ingredients') }}</div>
                        <div class="card-body">


                            <div class="form-group">
                                <label for="mealPreferences">{{ __('Meal preferences') }}</label>
                                <input type="text"
                                       class="form-control"
                                       name="mealPreferences"
                                       id="mealPreferences"
                                       value="{{ old('mealPreferences', $settings['mealPreferences'] ?? null) }}"
                                       aria-describedby="mealPreferencesHelp"
                                >
                                <small id="mealPreferencesHelp" class="form-text text-muted">
                                    {{ __('Comma-separated values') }}
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="mealAversion">{{ __('Meal excludes') }}</label>
                                <input type="text"
                                       class="form-control"
                                       name="mealAversion"
                                       id="mealAversion"
                                       value="{{ old('mealAversion', $settings['mealAversion'] ?? null) }}"
                                       aria-describedby="mealAversionHelp"
                                >
                                <small id="mealAversionHelp" class="form-text text-muted">
                                    {{ __('Comma-separated values') }}
                                </small>
                            </div>

                        </div>
                    </div>
                </section>

            </div>


            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
    </div>
@endsection
