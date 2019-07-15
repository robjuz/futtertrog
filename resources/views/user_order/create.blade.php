@extends('user_order.wrapper')

@section('wrapper-content')
    <form action="{{ route('order_items.store') }}" method="post">
        @csrf

        <div class="form-group">
            <label for="user_id" class="col-form-label-sm">
                {{__('User')}}
            </label>
            <select
                id="user_id"
                class="custom-select @error('user_id') 'is-invalid' @enderror"
                name="user_id"
            >
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>

            @error('user_id'))
                <div class="invalid-tooltip" role="alert">
                    <strong>{{ $message }}</strong>
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="meal_id" class="col-form-label-sm">
                {{__('Meal')}}
            </label>
            <select
                id="meal_id"
                class="custom-select @error('meal_id') 'is-invalid' @enderror"
                name="meal_id"
            >
                @foreach($meals as $meal)
                    <option value="{{ $meal->id }}">{{ $meal->title }}</option>
                @endforeach
            </select>

            @error('meal_id'))
            <div class="invalid-tooltip" role="alert">
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="quantity" class="col-form-label-sm">
                {{__('Quantity')}}
            </label>
            <input
                id="quantity"
                type="number"
                min="1"
                class="custom-select @error('quantity') 'is-invalid' @enderror"
                pattern="\d*"
                name="quantity"
            >

            @error('quantity'))
            <div class="invalid-tooltip" role="alert">
                <strong>{{ $message }}</strong>
            </div>
            @enderror
        </div>

        <input type="hidden" name="date" value="{{ $date }}"/>

        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
    </form>
@endsection
