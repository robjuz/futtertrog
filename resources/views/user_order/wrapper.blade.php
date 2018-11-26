@extends('layouts.app')

@section('content')

    <h1>
        <span>{{ __('Create order') }}</span>
        {{ isset($date) ? $date->format(__('futtertrog.date_format')) : null }}
    </h1>

    @yield('wrapper-content')

@endsection
