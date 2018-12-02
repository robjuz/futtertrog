@extends('layouts.app')

@section('content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ $user->name }}</div>

                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">{{__('Balance')}}</dt>
                            <dd class="col-sm-9">
                                <span class="{{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                                </span>
                            </dd>
                        </dl>


                    </div>
                </div>
            </div>
        </div>


@endsection