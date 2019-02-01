<div class="card">
    <h2 class="card-header">{{ __('Deposit history') }} </h2>

    <div class="list-group list-group-flush">
        @forelse ($deposits as $deposit)
            <div class="list-group-item">
                <div class="d-flex w-100 align-items-center">
                    <h3 class="mb-0 h5 text-nowrap {{ $deposit->value > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($deposit->value, 2, ',','.') }} €
                    </h3>
                    <small class="ml-auto"
                           title="{{ $deposit->created_at->format(__('futtertrog.datetime_format')) }}">{{ $deposit->created_at->diffForHumans() }}</small>
                </div>
                <p class="mb-2"> {{ $deposit->comment }} </p>
                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <form action="{{ route('deposits.destroy', $deposit) }}" method="post" class="text-right">
                        @csrf
                        @method('delete')

                        <button type="submit" class="btn btn-link btn-sm text-danger">
                            {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="alert alert-warning m-0" role="alert">
                <strong> {{ __('No results found') }}!</strong>
            </div>
        @endforelse
    </div>

    {{ $deposits->links() }}
</div>
