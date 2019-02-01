@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="py-3 d-md-flex justify-content-between align-items-end">
            <h2>
                {{ $user->name }}
            </h2>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-link">
                {{ __('Edit') }}
            </a>
        </div>
        <div class="row justify-content-center">

            <div class="col-12 col-md-6">
                <div class="card mb-3">
                    <div class="card-header">{{ __('Balance') }}</div>

                    <div class="card-body">
                        <span class="{{  $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format( $user->balance, 2, ',','.') }} €
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card mb-3">
                    <div class="card-header">{{ __('New deposit') }}</div>

                    <div class="card-body">
                        <form action="{{route('deposits.store')}}" method="post" role="form">
                            @csrf()
                            <input type="hidden" name="user_id" value="{{$user->id}}">

                            <div class="form-group">
                                <label for="value" class="col-form-label-sm">{{ __('Value') }}</label>
                                <div>
                                    <input type="number" class="form-control" name="value" id="value" step="0.01">
                                    @if ($errors->has('value'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('value') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="comment" class="col-form-label-sm">{{ __('Comment') }}</label>
                                <div>
                                    <textarea class="form-control" name="comment" id="comment"></textarea>
                                    @if ($errors->has('value'))
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('comment') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                @include('order_history')
            </div>
            <div class="col-12 col-lg-6">
                @include('deposit_history')
            </div>
        </div>


@endsection
