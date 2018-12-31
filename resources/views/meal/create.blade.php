@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">{{ __('New meal') }}</div>

                    <div class="card-body">
                        <form action="{{ route('meals.store') }}" method="post">
                            @csrf

                            <div class="form-row">
                                <div class="form-group col-12 col-md-4">
                                    <label for="title" class="col-form-label-sm">
                                        {{__('Title')}}
                                    </label>

                                    <input type="text"
                                           name="title"
                                           id="title"
                                           class="form-control{{ $errors->has('title') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('title') }}"
                                    >

                                    @if ($errors->has('title'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('title') }}</strong>
                                        </span>
                                    @endif
                                </div>

                                <div class="form-group col-12 col-md-4">
                                    <label for="date" class="col-form-label-sm">
                                        {{ __('Date') }}
                                    </label>

                                    <input type="date"
                                           name="date"
                                           id="date"
                                           class="form-control{{ $errors->has('date') ? ' is-invalid' : '' }}"
                                           required
                                           value="{{ old('date') }}"
                                    >

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
                                           value="{{ old('price') }}"
                                    >

                                    @if ($errors->has('price'))
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $errors->first('price') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description" class="col-form-label-sm">
                                    {{ __('Description') }}
                                </label>

                                <textarea name="description"
                                          id="description"
                                          class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}"
                                          required
                                >{{ old('description') }}</textarea>

                                @if ($errors->has('description'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()