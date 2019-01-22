@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Edit Meal') }}</div>

                    <div class="card-body">
                        <form action="{{ route('meals.update', $meal) }}" method="post">
                            @method('put')
                            @csrf

                            <div class="form-row">
                                <div class="form-group col-12">
                                    <label for="title" class="col-form-label-sm">
                                        {{__('Title')}}
                                    </label>

                                    <input type="text"
                                           name="title"
                                           id="title"
                                           class="form-control{{ $errors->has('title') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('title', $meal->title) }}"
                                    >

                                    @if ($errors->has('title'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('title') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                <div class="form-group col-12 col-md-4">
                                    <label for="provider" class="col-form-label-sm">{{ __('Provider') }}</label>

                                    <select class="custom-select {{ $errors->has('provider') ? ' is-invalid' : '' }}"
                                            name="provider"
                                            id="provider"
                                    >
                                        <option value="{{ \App\Meal::PROVIDER_HOLZKE }}" {{ old('provider', $meal->provider) == \App\Meal::PROVIDER_HOLZKE ? ' selected' : '' }}>{{ \App\Meal::PROVIDER_HOLZKE }}</option>
                                        <option value="{{ \App\Meal::PROVIDER_PARADIES_PIZZA }}" {{ old('provider', $meal->provider) == \App\Meal::PROVIDER_PARADIES_PIZZA ? ' selected' : '' }}>{{ \App\Meal::PROVIDER_PARADIES_PIZZA }}</option>
                                    </select>

                                    @if ($errors->has('provider'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('provider') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                <div class="form-group col-12 col-md-4">
                                    <label for="date" class="col-form-label-sm">
                                        {{ __('Date') }}
                                    </label>

                                    <input type="date"
                                           id="date"
                                           class="form-control{{ $errors->has('date') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('date', $meal->date->toDateString()) }}"
                                    >
                                    <input type="hidden" name="date" id="date_raw"
                                           value="{{ old('date', $meal->date->toDateString()) }}">

                                    @if ($errors->has('date'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('date') }}</strong>
                                        </span>
                                    @endif
                                </div>


                                <div class="form-group col-12 col-md-4">
                                    <label for="price" class="col-form-label-sm">
                                        {{ __('Price') }}
                                    </label>

                                    <input type="number"
                                           name="price"
                                           id="price"
                                           class="form-control{{ $errors->has('price') ? ' is-invalid' : '' }}"
                                           required
                                           min="0"
                                           step="0.01"
                                           value="{{ old('price', $meal->price) }}"
                                    >

                                    @if ($errors->has('price'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('price') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                <div class="form-group col-12">
                                    <label for="description" class="col-form-label-sm">
                                        {{ __('Description') }}
                                    </label>

                                    <textarea name="description"
                                              id="description"
                                              class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}"
                                    >{{ old('description', $meal->description) }}</textarea>

                                    @if ($errors->has('description'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()