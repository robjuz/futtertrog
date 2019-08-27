@extends('layouts.app')

@section('content')

    @include('partials.user_menu')

    <h1>{{ __('User index') }}</h1>

    <table>
        <thead>
            <tr>
                <th>{{__('Name')}}</th>
                <th>{{__('Email')}}</th>
                <th>{{__('Balance')}}</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <a href="{{ route('users.show', $user) }}">
                            {{ $user->name }}
                        </a>
                    </td>

                    <td>{{ $user->email }}</td>

                    <td>
                        <span class="{{ $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($user->balance, 2, ',','.') }} €
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
