@extends('layouts.app')

@section('content')


    <div class="container user-index">

        <div class="card">
            <div class="card-header d-flex align-items-center">
                {{ __('User index') }}

                <a href={{ route('deposites.transfer' )}} class="btn btn-link ml-auto">{{ __('New money transfer') }}</a>

                <a href="{{ route('users.create') }}" class="btn btn-link">{{ __('New user') }}</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="d-none d-md-block col-1"></div>
                    <div class="col-7 col-sm-4">{{__('Name')}}</div>
                    <div class="d-none d-sm-block col-sm-5">{{__('Email')}}</div>
                    <div class="col-5 col-sm-2">{{__('Balance')}}</div>
                </div>
                @foreach($users as $user)
                    <div class="row mt-3 align-items-center">
                        <div class="d-none d-md-block col-1">
                            <img src="{{ $user->gravatarUrl(100) }}" class="rounded-circle img-fluid" alt="">
                        </div>
                        <div class="col-7 col-sm-4">
                            <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                        </div>
                        <div class="d-none d-sm-block col-sm-5">{{ $user->email }}</div>
                        <div class="col-5 col-sm-2">
                            <span class="{{ $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($user->balance, 2, ',','.') }} €
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>

@endsection
