@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">{{ __('Settings') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.store') }}">
                            @csrf

                            <div class="form-group pt-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="show_dog" value="0">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           name="show_dog"
                                           id="show_dog"
                                           {{ old('show_dog', $settings['show_dog']) ? 'checked' : '' }}
                                           value="1"
                                    >
                                    <label class="custom-control-label" for="show_dog">
                                        {{ __('Show dog') }}
                                    </label>
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