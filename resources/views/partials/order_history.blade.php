<div class="card">
    <h2 id="order-history" class="card-header d-flex align-items-center">
        {{ __('Order history') }}

        @if(request()->routeIs('home'))
            <a href="{{ route('meals.ical') }}" class="ml-auto btn btn-link">{{ __('Download as iCal') }}</a>
        @endif
    </h2>

    <ul class="list-group list-group-flush">
        @forelse ($orders as $orderItem)
            <li class="list-group-item {{ $loop->last ? ' border-bottom-0' : '' }}">
                <header class="d-flex w-100 align-items-center mb-2">
                    <h3 class="mb-0 h5 text-nowrap">{{ $orderItem->meal->title }}</h3>
                    <small class="ml-auto" title="{{ $orderItem->created_at->format(__('futtertrog.datetime_format')) }}">{{ $orderItem->created_at->diffForHumans() }}</small>
                </header>

                <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                    <div>{{  $orderItem->order->date->format(__('futtertrog.date_format'))  }}</div>
                    <div class="text-nowrap">{{ number_format($orderItem->meal->price, 2, ',','.') }} €</div>
                </div>

                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <form action="{{ route('order_items.destroy', $orderItem) }}" method="post" class="text-right">
                        @csrf
                        @method('delete')

                        <button type="submit" class="btn btn-link btn-sm text-danger">
                            {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </li>
        @empty
            <div class="alert alert-warning m-0" role="alert">
                <strong> {{ __('No results found') }}!</strong>
            </div>
        @endforelse
    </ul>

    {{ $orders->links() }}
</div>