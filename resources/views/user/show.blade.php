@extends('layouts.app')

@section('content')
    <h1>{{ $user->name }}</h1>

    <a href="{{ route('users.edit', $user) }}">
        {{ __('Edit') }}
    </a>

    @include('user._balance')

    @include('partials.deposit_history')

    @include('partials.order_history')

@endsection
