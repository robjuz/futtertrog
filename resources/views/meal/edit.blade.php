@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Edit Meal') }}</div>

                <div class="card-body">
                    <form action="{{ route('meals.update', $meal) }}" method="post">
                        @method('put')
                        @csrf

                        <div class="form-row">

                            <div class="form-group col-4">
                                <label for="title">{{__('Title')}}</label>
                                <input type="text"
                                       name="title"
                                       id="title"
                                       class="form-control"
                                       required
                                       value="{{ $meal->title }}"
                                >
                            </div>

                            <div class="form-group col-4">
                                <label for="date">Datum</label>
                                <input type="date"
                                       name="date"
                                       id="date"
                                       class="form-control"
                                       required
                                       value="{{ $meal->date->toDateString() }}"
                                >
                            </div>

                            <div class="form-group col-4">
                                <label for="price">Preis</label>
                                <input type="number"
                                       name="price"
                                       id="price"
                                       class="form-control"
                                       required
                                       min="0"
                                       step="0.01"
                                       value="{{ $meal->price }}"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Beschreibung</label>
                            <textarea name="description" id="description" class="form-control"
                                      required>{{ $meal->description }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

@endsection()