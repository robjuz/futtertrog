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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Settings') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.store') }}">
                            @csrf

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">{{ __('General') }}</label>
                                <div class="col-sm-9">

                                    <div class="form-group pb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="hidden" name="show_dog" value="0">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   name="show_dog"
                                                   id="show_dog"
                                                   {{ old('show_dog', $settings['show_dog'] ?? true) ? 'checked' : '' }}
                                                   value="1"
                                            >
                                            <label class="custom-control-label" for="show_dog">
                                                {{ __('Show dog') }}
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group pb-2">
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

                                    <div class="form-group pb-2">
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

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">{{ __('Ingredients') }}</label>
                                <div class="col-sm-9">

                                    <div class="form-group">
                                        <label for="includes">{{ __('includes') }}</label>
                                        <input type="text"
                                               class="form-control"
                                               name="includes"
                                               id="includes"
                                               value="{{ old('includes', $settings['includes'] ?? null) }}"
                                               aria-describedby="includesHelp"
                                        >
                                        <small id="includesHelp" class="form-text text-muted">
                                            {{ __('Comma-separated values') }}
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="excludes">{{ __('excludes') }}</label>
                                        <input type="text"
                                               class="form-control"
                                               name="excludes"
                                               id="excludes"
                                               value="{{ old('excludes', $settings['excludes'] ?? null) }}"
                                               aria-describedby="excludesHelp"
                                        >
                                        <small id="excludesHelp" class="form-text text-muted">
                                            {{ __('Comma-separated values') }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection