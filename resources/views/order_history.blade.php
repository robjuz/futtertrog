<div class="card mb-3">
    <h2 class="card-header">{{ __('Order history') }} </h2>

    <div class="list-group list-group-flush">
        @forelse ($orders as $orderItem)
            <div class="list-group-item">
                <div class="d-flex w-100 align-items-center mb-2">
                    <h3 class="mb-0 h5 text-nowrap">{{ $orderItem->meal->title }}</h3>
                    <small class="ml-auto"
                           title="{{ $orderItem->created_at->format(__('futtertrog.datetime_format')) }}">{{ $orderItem->created_at->diffForHumans() }}</small>
                </div>
                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                    <div>{{  $orderItem->order->date->format(__('futtertrog.date_format'))  }}</div>
                    <div class="text-nowrap">{{ number_format($orderItem->meal->price, 2, ',','.') }} €</div>
                </div>
                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <form action="{{ route('orders.destroy', $orderItem) }}" method="post" class="text-right">
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

    {{ $orders->links() }}
</div>