@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('New Meal') }}</div>

                <div class="card-body">
                    <form action="{{ route('meals.store') }}" method="post">
                        @csrf
                        <div class="form-row">

                            <div class="form-group col-4">
                                <label for="title">{{__('Title')}}</label>
                                <input type="text"
                                       name="title" id="title" class="form-control{{ $errors->has('title') ? ' is-invalid' : '' }}" required>

                                @if ($errors->has('title'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-4">
                                <label for="date">{{__('Date')}}</label>
                                <input type="date"
                                       name="date" id="date" class="form-control{{ $errors->has('date') ? ' is-invalid' : '' }}" required>

                                @if ($errors->has('date'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('date') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group col-4">
                                <label for="price">{{__('Price')}}</label>
                                <input type="number"
                                       name="price" id="price" class="form-control{{ $errors->has('price') ? ' is-invalid' : '' }}" required min="0" step="0.01">

                                @if ($errors->has('price'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('price') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">{{__('Description')}}</label>
                            <textarea name="description" id="description" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" required></textarea>

                            @if ($errors->has('description'))
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                            @endif
                        </div>

                        <div class="form-group">
                            <input type="submit" class="btn btn-primary" name="saveAndNew" value="Speichern und neu">
                            <input type="submit" class="btn btn-outline-primary" value="Speichern">
                        </div>

                    </form>
                </div>

@endsection()