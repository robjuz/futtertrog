@extends('layouts.app')

@section('content')

    <h1>{{ __('User index') }}</h1>

    @include('partials.user_menu')

    <table>
        <thead>
            <tr>
                <th>{{__('Name')}}</th>
                <th class="collapsible">{{__('Email')}}</th>
                <th class="money">{{__('Balance')}}</th>
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

                    <td class="collapsible">{{ $user->email }}</td>

                    <td class="money">
                        <span class="{{ $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format(0.01 * $user->balance, 2, ',','.') }} €
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
