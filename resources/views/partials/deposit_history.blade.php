<h2>{{ __('Deposit history') }} </h2>

@if( count($deposits->items()) > 0)
    <ul>
        @foreach ($deposits as $deposit)
            <li>
                <h3 class="{{ $deposit->value > 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($deposit->value, 2, ',','.') }} €
                </h3>
                <small title="{{ $deposit->created_at->format(__('futtertrog.datetime_format')) }}">{{ $deposit->created_at->diffForHumans() }}</small>

                <p> {{ $deposit->comment }} </p>

                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <form action="{{ route('deposits.destroy', $deposit) }}" method="post">
                        @csrf
                        @method('delete')

                        <button type="submit">
                            {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p> {{ __('No results found') }}!</p>
@endif

{{ $deposits->links() }}


