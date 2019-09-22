<h2>{{ __('Deposit history') }} </h2>

@if( count($deposits->items()) > 0)
    <table>
        <thead>
            <tr>
                <th>{{__('Betrag')}}</th>
                <th>{{__('Datum')}}</th>
                <th class="collapsible">{{__('Kommentar')}}</th>
                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <th></th>
                @endif
            </tr>
        </thead>
        @foreach ($deposits as $deposit)
            <tr>
                <td class="money {{ $deposit->value > 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($deposit->value, 2, ',','.') }}€
                </td>
                <td title="{{ $deposit->created_at->format(__('futtertrog.datetime_format')) }}">{{ $deposit->created_at->diffForHumans() }}</td>

                <td class="collapsible"> {{ $deposit->comment }} </td>

                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <td>
                        <form action="{{ route('deposits.destroy', $deposit) }}" method="post">
                            @csrf
                            @method('delete')

                            <button type="submit">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </td>
                @endif
            </tr>
        @endforeach
    </table>
@else
    <p> {{ __('No results found') }}!</p>
@endif

{{ $deposits->links() }}


