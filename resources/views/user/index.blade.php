@extends('layouts.app')

@section('content')

    <h1>{{ __('User index') }}</h1>

    <nav class="sub-menu">

        <a href="{{ route('users.create') }}"
            {{ request()->routeIs('users.create') ? ' aria-current="page"' : '' }}
        >
            {{ __('New user') }}
        </a>

        <a href="{{ route('notifications.create' )}}"
            {{ request()->routeIs('notifications.create') ? ' aria-current="page"' : '' }}
        >
            {{ __('New notification') }}
        </a>
    </nav>



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
                        <span class="{{ $user->balance > 0 ? 'positive-value' : 'negative-value' }}">
                            @money($user->balance)
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
