@extends('layouts.admin')

@section('content')
    <h1>{{ __('Manage meals') }}</h1>
    <a href="{{ route('meals.create') }}">{{ __('New meal') }}</a>
    <table>
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Meal') }}</th><th>{{ __('Actions') }}</th></tr></thead>
        <tbody>
            @foreach($meals as $meal)
                <tr>
                    <td>{{ $meal->date->format(__('futtertrog.date_format')) }}</td>
                    <td>{{ $meal->title }}</td>
                    <td><a href="{{ route('meals.edit', $meal) }}">{{ __('Edit') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $meals->links() }}
@endsection
