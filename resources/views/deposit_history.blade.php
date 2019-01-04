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
                </tr>
            @endforeach

            </tbody>
        </table>
        {{ $deposits->links() }}
    </div>
</div>