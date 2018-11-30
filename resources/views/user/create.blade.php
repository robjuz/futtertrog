@extends('layouts.app')

@section('content')
    <div class="container">
        <form action="{{ route('users.store') }}" method="post">
            {{ csrf_field() }}

            <legend>Neuer Benutzer</legend>

            <div class="form-row">
                
                <div class="form-group col-4">
                    <label for="title"></label>
                    <input type="text"
                           name="title" id="title" class="form-control" required>
                </div>
                
                <div class="form-group col-4">
                    <label for="date">Datum</label>
                    <input type="date"
                           name="date" id="date" class="form-control" required>
                </div>
                
                <div class="form-group col-4">
                    <label for="price">Preis</label>
                    <input type="number"
                           name="price" id="price" class="form-control" required min="0" step="0.01">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea name="description" id="description" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <input type="submit" class="btn btn-primary" name="saveAndNew" value="Speichern und neu">
                <input type="submit" class="btn btn-secondary" value="Speichern">
            </div>

        </form>
    </div>

@endsection()