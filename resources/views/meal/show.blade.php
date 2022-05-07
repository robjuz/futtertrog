@extends('layouts.app')

@section('content')

    <h1>{{ $meal->title }}</h1>

    <span>{{ $meal->provider }}</span>
    <span>{{ $meal->date_from->format(trans('futtertrog.date_format')) }} - {{ $meal->date_to->format(trans('futtertrog.date_format')) }}</span>
    <span>{{ $meal->price }}</span>

    <p>{{ $meal->description }}</p>

@endsection
