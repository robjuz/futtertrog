<div class="border-top border-bottom py-3 d-flex flex-column h-100">
    <div class="actions d-flex mb-2">
        @can('update', $meal)
            <a href="{{ route('meals.edit', $meal) }}" class="btn btn-link text-info px-0">
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

        @if($orderItem = $todayOrders->firstWhere('meal_id', $meal->id))
            <form onsubmit="toggleOrder(event)" action="{{ route('order_items.destroy', $orderItem) }}" method="post"
                  class="d-flex justify-content-end ml-auto">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-outline-danger btn-submit">
                    {{ __('Delete order') }}
                    <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </button>
            </form>
        @else
            <form onsubmit="toggleOrder(event)" action="{{ route('order_items.store') }}" method="post"
                  class="d-flex justify-content-end ml-auto">
                @csrf
                <input type="hidden" name="date" value="{{ $requestedDate->toDateString() }}"/>
                <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
                <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>

                <input type="number"
                       class="form-control"
                       name="quantity"
                       min="1"
                       pattern="\d*"
                       value="1"
                       style="width: 80px;"
                >
                <button type="submit" class="btn btn-outline-primary btn-submit">
                    {{ __('Place order') }}
                    <span class="d-none spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </button>
            </form>
        @endif

    </div>

    <div class="d-sm-flex mb-2">

        <h4 class="d-flex justify-content-between align-items-center flex-grow-1 mb-sm-0">
            <div class="{{ $meal->getTitleClasses() }} mr-auto">
                {{ $meal->title }}
            </div>
            <small class="text-nowrap">{{ number_format($meal->price, 2, ',', '.') }} €</small>
        </h4>

    </div>

    @if (!(auth()->user()->settings[\App\User::SETTING_HIDE_ORDERING_MEAL_DESCRIPTION] ?? false))
        <p class="text-dark order-3 description">{{ $meal->description }}</p>
    @endif
</div>
