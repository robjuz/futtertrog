@extends('layouts.app')

@section('content')
    <h1>{{ __('My orders') }}</h1>
    @include('partials.order_history')
@endsection
