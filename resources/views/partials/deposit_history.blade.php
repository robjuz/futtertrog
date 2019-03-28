<div class="card">
    <h2 id="deposit-history" class="card-header">{{ __('Deposit history') }} </h2>

    @if( count($deposits->items()) > 0)
        <ul class="list-group list-group-flush">
            @foreach ($deposits as $deposit)
                <li class="list-group-item {{ $loop->last ? ' border-bottom-0' : '' }}">
                    <div class="d-flex w-100 align-items-center mb-2">
                        <h3 class="mb-0 h5 text-nowrap {{ $deposit->value > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($deposit->value, 2, ',','.') }} €
                        </h3>
                        <small class="ml-auto" title="{{ $deposit->created_at->format(__('futtertrog.datetime_format')) }}">{{ $deposit->created_at->diffForHumans() }}</small>
                    </div>

                    <p class="mb-0"> {{ $deposit->comment }} </p>

                    @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                        <form action="{{ route('deposits.destroy', $deposit) }}" method="post" class="text-right">
                            @csrf
                            @method('delete')

                            <button type="submit" class="btn btn-link btn-sm text-danger">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <div class="alert alert-warning m-0" role="alert">
            <strong> {{ __('No results found') }}!</strong>
        </div>
    @endif

    <nav>
        {{ $deposits->links() }}
    </nav>
</div>
