@extends('user_order.wrapper')

@section('wrapper-content')
    <form action="{{ route('order_items.create') }}" method="get">
        <div class="form-group">
            <label for="date" class="col-form-label-sm">
                {{__('Date')}}
            </label>

            <input type="date"
                   name="date"
                   id="date"
                   class="form-control @error('date') 'is-invalid @enderror"
                   required
            >
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Next') }}</button>
    </form>
@endsection
