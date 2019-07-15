@extends('layouts.app')

@section('content')
    <main class="container flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <h1 class="card-header">{{ __('New meal') }}</h1>

                    <div class="card-body">
                        <form action="{{ route('meals.store') }}" method="post">
                            @csrf

                            <div class="form-row">
                                <div class="form-group col-12 col-md-8">
                                    <label for="title" class="col-form-label-sm">
                                        {{__('Title')}}
                                    </label>

                                    <input type="text"
                                           name="title"
                                           id="title"
                                           class="form-control @error('title') 'is-invalid' @enderror"
                                           required
                                           value="{{ old('title') }}"
                                    >

                                    @error('title'))
                                    <div class="invalid-tooltip" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-4">
                                    <label for="price" class="col-form-label-sm">
                                        {{ __('Price') }}
                                    </label>

                                    <div class="input-group">
                                        <input
                                            id="price"
                                            type="number"
                                            name="price"
                                            class="form-control @error('price') 'is-invalid' @enderror"
                                            pattern="\d*"
                                            required
                                            min="0"
                                            step="0.01"
                                            value="{{ old('price') }}"
                                        >

                                        @error('price'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </div>
                                        @enderror

                                        <div class="input-group-append">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="date_from" class="col-form-label-sm">
                                        {{ __('From') }}
                                    </label>

                                    <input
                                        id="date_from"
                                        type="date"
                                        name="date_from"
                                        class="form-control @error('date_from') 'is-invalid' @enderror"
                                        required
                                        value="{{ old('date_from') }}"
                                    >

                                    @error('date_from'))
                                    <div class="invalid-tooltip" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="date_to" class="col-form-label-sm">
                                        {{ __('To') }}
                                    </label>

                                    <input
                                        id="date_to"
                                        type="date"
                                        name="date_to"
                                        class="form-control @error('date_to') 'is-invalid' @enderror"
                                        required
                                        value="{{ old('date_to') }}"
                                    >

                                    @error('date_to'))
                                    <div class="invalid-tooltip" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group col-12">
                                    <label for="description" class="col-form-label-sm">
                                        {{ __('Description') }}
                                    </label>

                                    <textarea
                                        id="description"
                                        name="description"
                                        class="form-control @error('description') 'is-invalid' @enderror"
                                    >{{ old('description') }}</textarea>

                                    @error('description'))
                                    <div class="invalid-tooltip" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input
                                        id="notify"
                                        class="custom-control-input"
                                        name="notify"
                                        type="checkbox"
                                        {{ old('notify') ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="notify">
                                        {{ __('Notify users') }}
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection()
