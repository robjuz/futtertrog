@extends('layouts.app')

@section('content')
    <div class="container">
        <form action="{{ route('meals.update', $meal) }}" method="post">
            {{ method_field('put') }}
            {{ csrf_field() }}

            <legend>Essen bearbeiten</legend>

            <div class="form-row">

                <div class="form-group col-4">
                    <label for="title">Titel</label>
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
                <textarea name="description" id="description" class="form-control" required>{{ $meal->description }}</textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary text-white">{{ __('Save') }}</button>
            </div>

        </form>
    </div>

@endsection()