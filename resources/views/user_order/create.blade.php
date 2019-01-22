@extends('user_order.wrapper')

@section('wrapper-content')
    <form action="{{ route('user_meals.store') }}" method="post">
        @csrf

        <div class="form-group">
            <label for="user_id" class="col-form-label-sm">
                {{__('User')}}
            </label>
            <select class="custom-select {{ $errors->has('user_id') ? ' is-invalid' : '' }}"
                    name="user_id"
                    id="user_id"
            >
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            @if ($errors->has('user_id'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $errors->first('user_id') }}</strong>
                </span>
            @endif
        </div>

        <div class="form-group">
            <label for="meal_id" class="col-form-label-sm">
                {{__('Meal')}}
            </label>
            <select class="custom-select {{ $errors->has('meal_id') ? ' is-invalid' : '' }}"
                    name="meal_id"
                    id="meal_id"
            >
                @foreach($meals as $meal)
                    <option value="{{ $meal->id }}">{{ $meal->title }}</option>
                @endforeach
            </select>

            @if ($errors->has('meal_id'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $errors->first('meal_id') }}</strong>
                </span>
            @endif
        </div>

        <div class="form-group">
            <label for="quantity" class="col-form-label-sm">
                {{__('Quantity')}}
            </label>
            <input type="number"
                   min="1"
                   class="form-control {{ $errors->has('quantity') ? ' is-invalid' : '' }}"
                   name="quantity"
                   id="quantity"
            >

            @if ($errors->has('quantity'))
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $errors->first('quantity') }}</strong>
                </span>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </form>
@endsection