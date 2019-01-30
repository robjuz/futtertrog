<div class="card mb-3">
    <h2 class="card-header">{{ __('Order history') }} </h2>

    <div class="card-body">
        <table class="table table-hover">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ __('Date') }}</th>
                <th scope="col">{{ __('Title') }}</th>
                <th scope="col">{{ __('Price') }}</th>
                <th scope="col">{{ __('Quantity') }}</th>
                @admin()
                <th scope="col">{{ __('Delete') }}</th>
                @endadmin
            </tr>
            </thead>
            <tbody>
            @foreach ($orders as $orderItem)
                <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>{{ $orderItem->order->date->format(__('futtertrog.d.m.Y')) }}</td>
                    <td>{{ $orderItem->meal->title }}</td>
                    <td class="text-nowrap">{{ number_format($orderItem->meal->price, 2, ',','.') }} €</td>
                    <td class="text-center">{{ $orderItem->quantity }}</td>
                    @admin()
                    <td>
                        <form action="{{ route('orders.destroy', $orderItem) }}" method="post">
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
        {{ $orders->links() }}
    </div>
</div>