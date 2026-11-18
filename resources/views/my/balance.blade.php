@extends('layouts.app')

@section('content')
    <h1>{{ __('My balance') }}</h1>
    @include('user._balance')
    @include('partials.deposit_history')
@endsection
