@extends('layouts.app')

@section('content')

    <h1>{{__('Pot generator')}}</h1>

    <form action="/pot-generator" method="POST">
        @csrf

        <fieldset>
            <legend>{{__('Lid')}}</legend>

            <label for="lid-width">{{__('Width')}}</label>
            <input id="lid-width" name="lidWidth" type="number" value="320" min="50" max="500" autofocus>

            <label for="lid-handle-radius">{{__('Handle radius')}}</label>
            <input id="lid-handle-radius" name="lidHandleRadius" type="number" value="15" min="10" max="30">

            <label for="lid-color">
                <span>{{__('Color')}}</span>
                <small>{{__('in HEX notation, e.g. #555 or #fefefe')}}</small>
                @error('lidColor')
                    <span>{{ $message }}</span>
                @enderror
            </label>
            <input id="lid-color" name="lidColor" type="color" value="{{ old('lidColor', '#f28705') }}">
        </fieldset>

        <fieldset>
            <legend>{{__('Pot')}}</legend>

            <label for="pot-width">{{__('Width')}}</label>
            <input id="pot-width" name="potWidth" type="number" value="300" min="50" max="500">

            <label for="pot-height">{{__('Height')}}</label>
            <input id="pot-height" name="potHeight" type="number" value="200" min="50" max="400">

            <label for="pot-radius">{{__('Bottom radius')}}</label>
            <input id="pot-radius" name="potRadius" type="number" value="15" min="10" max="30">

            <label for="pot-color">
                <span>{{__('Color')}}</span>
                <small>{{__('in HEX notation, e.g. #FFF or #fefefe')}}</small>
            </label>
            <input id="pot-color" name="potColor" type="color" value="{{ old('potColor', '#f28705') }}">
        </fieldset>

        <button type="submit">{{__('Create')}}</button>
    </form>
@endsection
