@extends('layouts.app')

@section('content')

    <h1>{{ __('Deposit index') }}</h1>

    <nav class="sub-menu">
        <a
            href="{{ route('deposits.create' )}}"
            {{ request()->routeIs('deposits.create') ? ' aria-current="page"' : '' }}
        >
            {{ __('New deposit') }}
        </a>

        <a
            href="{{ route('deposits.transfer' )}}"
            {{ request()->routeIs('deposits.transfer') ? ' aria-current="page"' : '' }}
        >
            {{ __('New money transfer') }}
        </a>
    </nav>

    <table>
        <thead>
            <tr>
                <th>{{__('User')}}</th>
                <th>{{__('Value')}}</th>
                <th class="collapsible">{{__('Comment')}}</th>
                <th>{{__('Created at')}}</th>
                <th>{{__('Actions')}}</th>
            </tr>
        </thead>

        <tbody>
            @foreach($deposits as $deposit)
                <tr>
                    <td>
                        {{ $deposit->user->name }}
                    </td>

                    <td>
                        <span class="{{ $deposit->value > 0 ? 'positive-value' : 'negative-value' }}">
                            @money($deposit->value)
                        </span>
                    </td>

                    <td class="collapsible">
                        {{ $deposit->comment }}
                    </td>

                    <td>
                        {{ $deposit->created_at }}
                    </td>

                    <td>
                        <a href="{{route('deposits.edit', $deposit)}}">
                            @svg('solid/pen', ['aria-hidden', 'focusable="false"'])
                            <span class="sr-only">{{ __('Edit') }}</span>
                        </a>
                        <form method="post" action="{{ route('deposits.destroy', $deposit) }}">
                            @csrf()
                            @method('delete')
                            <button type="submit">
                                @svg('solid/trash', ['aria-hidden', 'focusable="false"'])
                                <span class="sr-only">{{ __('Delete') }}</span>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $deposit->paginate() }}
@endsection
