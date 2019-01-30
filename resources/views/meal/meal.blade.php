<div class="border-top border-bottom py-3">
    @can('update', $meal)
        <a href="{{ route('meals.edit', $meal) }}" class="btn btn-link text-info pl-0">
            {{ __('Edit') }}
        </a>
    @endcan

    @can('delete', $meal)
        <form action="{{ route('meals.destroy', $meal) }}" method="post" class="d-inline-block">
            @method('delete')
            @csrf
            <button type="submit" class="btn btn-link text-danger">{{ __('Delete') }}</button>
        </form>
    @endcan

    <h4 class="d-flex justify-content-between">
        {{ $meal->title }}
        <div class="d-flex align-items-center justify-content-end">
            <small>{{ number_format($meal->price, 2, ',', '.') }} €</small>

            @if($orderItem = $todayOrders->firstWhere('meal_id', $meal->id))
                <form onsubmit="toggleOrder(event)" action="{{ route('orders.destroy', $orderItem) }}"
                      method="post" class="ml-3">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-outline-danger btn-sm btn-submit">
                        {{ __('Delete order') }}
                        <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            @else
                <form onsubmit="toggleOrder(event)" action="{{ route('orders.store') }}" method="post"
                      class="d-flex ml-3 flex-shrink-1">
                    @csrf
                    <input type="hidden" name="date" value="{{ $requestedDate->toDateString() }}"/>
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
                    <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>

                    <input type="number"
                           class="form-control"
                           name="quantity"
                           min="1"
                           value="1"
                           style="width: 80px;"
                    >
                    <button type="submit" class="btn btn-outline-primary btn-sm btn-submit">
                        {{ __('Place order') }}
                        <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            @endcan
        </div>
    </h4>

    <p class="text-dark">{{ $meal->description }}</p>
</div>