@extends('layouts.app')

@push('main-classes')
    user-show
@endpush

@section('content')
    <h1>{{ $user->name }}</h1>

    <section>
        <a href="{{ route('users.edit', $user) }}">
            {{ __('Edit') }}
        </a>
    </section>

    <section>
        @include('user._balance', ['balance' => $user->balance])
    </section>

    <section>
        @include('partials.deposit_history')
    </section>

    <section>
        @include('partials.order_history')
    </section>

@endsection
