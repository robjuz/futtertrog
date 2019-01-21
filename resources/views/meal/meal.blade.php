<div id="meal_{{ $meal->id }}" class="border-top border-bottom py-3">
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

            @if($orders->contains($meal))
                @can('disorder', $meal)
                    <form onsubmit="order(event, 'DELETE')" action="{{ route('user_meals.destroy', $meal) }}" method="post" class="ml-3">
                        @csrf
                        @method('delete')
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            ( {{ $orders->firstWhere('id', $meal->id)->pivot->quantity }} ) {{ __('Delete order') }}
                            <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                @endcan
            @else
                @can('order', $meal)
                    <form onsubmit="order(event, 'POST')"  action="{{ route('user_meals.store') }}" method="post" class="d-flex ml-3 flex-shrink-1">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
                        <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>

                        <input type="number"
                               class="form-control"
                               name="quantity"
                               min="1"
                               value="1"
                               style="width: 80px;"
                        >
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            {{ __('Place order') }}
                            <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                @endcan
            @endif
        </div>
    </h4>

    <p class="text-dark">{{ $meal->description }}</p>
</div>