@extends('layouts.app')

@section('content')


    <div class="container">

        <table class="table">
            <thead>
            <th>{{__('Name')}}</th>
            <th>{{__('Email')}}</th>
            <th>{{__('Balance')}}</th>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="{{ auth()->user()->balance > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format(auth()->user()->balance, 2, ',','.') }} €
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

    </div>

@endsection