@extends('layouts.app')

@section('content')
    <div class="container flex-grow-1">
        <div class="py-3 d-md-flex align-items-end">
            <h1 class="ml-3 mr-auto">
                {{ $user->name }}
            </h1>

            <a href="{{ route('users.edit', $user) }}" class="btn btn-link">
                {{ __('Edit') }}
            </a>
        </div>
        <div class="row justify-content-center">

            <div class="col-md-6 mb-3">
                <div class="mb-3">
                    @include('user._balance')
                </div>

                @include('partials.deposit_history')
            </div>

            <div class="col-md-6 mb-3">

                @include('partials.order_history')
            </div>
        </div>
    </div>
@endsection
