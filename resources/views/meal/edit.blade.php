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
                                <div class="form-group col-12 col-md-8">
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
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('title') }}</strong>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group col-12 col-md-4">
                                    <label for="price" class="col-form-label-sm">
                                        {{ __('Price') }}
                                    </label>

                                    <div class="input-group">
                                        <input type="number"
                                               name="price"
                                               id="price"
                                               class="form-control{{ $errors->has('price') ? ' is-invalid' : '' }}"
                                               pattern="\d*"
                                               required
                                               min="0"
                                               step="0.01"
                                               value="{{ old('price', $meal->price) }}"
                                        >

                                        @if ($errors->has('price'))
                                            <div class="invalid-tooltip" role="alert">
                                                <strong>{{ $errors->first('price') }}</strong>
                                            </div>
                                        @endif
                                        <div class="input-group-append">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>

                                </div>


                                <div class="form-group col-12 col-md-6">
                                    <label for="date_from" class="col-form-label-sm">
                                        {{ __('From') }}
                                    </label>

                                    <input type="date"
                                           id="date_from"
                                           name="date_from"
                                           class="form-control{{ $errors->has('date_from') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('date_from', $meal->date_from->toDateString()) }}"
                                    >

                                    @if ($errors->has('date_from'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('date_from') }}</strong>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label for="date_to" class="col-form-label-sm">
                                        {{ __('To') }}
                                    </label>

                                    <input type="date"
                                           id="date_to"
                                           name="date_to"
                                           class="form-control{{ $errors->has('date_to') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('date_to', $meal->date_to->toDateString()) }}"
                                    >

                                    @if ($errors->has('date_to'))
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('date_to') }}</strong>
                                        </div>
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
                                        <div class="invalid-tooltip" role="alert">
                                            <strong>{{ $errors->first('description') }}</strong>
                                        </div>
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