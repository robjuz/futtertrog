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
                <th>{{ __('Actions') }}</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        @if($user->deleted_at)
                            {{ $user->name }}
                        @else
                            <a href="{{ route('users.show', $user) }}">
                                {{ $user->name }}
                            </a>
                        @endif
                    </td>

                    <td class="collapsible">{{ $user->email }}</td>

                    <td class="money">
                        <span class="{{ $user->balance > 0 ? 'positive-value' : 'negative-value' }}">
                            @money($user->balance)
                        </span>
                    </td>

                    <td>
                        @if($user->deleted_at)
                            <form action="{{ route('users.restore', $user) }}" method="POST">
                                @csrf

                                <button type="submit">
                                    @svg('solid/trash-restore', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                                    {{  __('Restore') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('users.destroy', $user) }}" method="POST">
                                @method('delete')
                                @csrf

                                <button type="submit">
                                    @svg('solid/trash', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
                                    {{  __('Delete') }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
