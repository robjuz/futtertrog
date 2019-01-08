<div class="card">
    <div class="card-header">{{ __('Deposit history') }} </div>

    <div class="card-body">
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __('Value') }}</th>
                <th scope="col">{{ __('Created at') }}</th>
                <th scope="col">{{ __('Comment') }}</th>
                @admin()
                <th scope="col">{{ __('Delete') }}</th>
                @endadmin
            </tr>
            </thead>
            <tbody>
            @foreach ($deposits as $deposit)
                <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>
                        <span class="{{ $deposit->value > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($deposit->value, 2, ',','.') }} €
                        </span>
                    </td>
                    <td>{{ $deposit->created_at->format(__('futtertrog.d.m.Y')) }}</td>
                    <td>{{ $deposit->comment }}</td>
                    @admin()
                    <td>
                        <form action="{{ route('deposits.destroy', $deposit) }}" method="post">
                            @csrf
                            @method('delete')

                            <button type="submit" class="btn btn-link btn-sm text-danger">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </td>
                    @endadmin
                </tr>
            @endforeach

            </tbody>
        </table>
        {{ $deposits->links() }}
    </div>
</div>