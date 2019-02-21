@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="py-3 d-md-flex align-items-end">
            <img src="{{ $user->gravatarUrl(160) }}" class="rounded-circle" alt="" width="80" height="80">
            <h2 class="ml-3 mr-auto">
                {{ $user->name }}
            </h2>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-link">
                {{ __('Edit') }}
            </a>
        </div>
        <div class="row justify-content-center">

            <div class="col-md-6 mb-3">
                @include('user.balance')

                @include('user.new-deposit')
            </div>

            <div class="col-md-6 mb-3">
                <div class="mb-3">
                @include('partials.deposit_history')
                </div>
                @include('partials.order_history')
            </div>
        </div>


@endsection
