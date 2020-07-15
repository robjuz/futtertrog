
<h4>
    @can('update', $meal)
        <a href="{{ route('meals.edit', $meal) }}">
            {{ $meal->title }}
        </a>
    @else
        <span>
            {{ $meal->title }}
        </span>
    @endcan

    @if($meal->isHated)
        @svg('solid/skull-crossbones', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
    @elseif($meal->isPreferred)
        @svg('solid/heart', ['role="presentation"', 'aria-hidden="true"', 'focusable="false"'])
    @endif
</h4>

<small class="money">@money($meal->price)</small>

@if($meal->image)
    <img src="{{ $meal->image }}" alt="">
@endif

@if (!(auth()->user()->settings[\App\User::SETTING_HIDE_ORDERING_MEAL_DESCRIPTION] ?? false))
    <p>{{ $meal->description }}</p>
@endif

@if($orderItem = $todayOrders->firstWhere('meal_id', $meal->id))
    @can('delete', $orderItem)
        <form action="{{ route('order_items.destroy', $orderItem) }}" method="post">
            @csrf
            @method('delete')
            <p> {{ trans_choice('futtertrog.portions_ordered', $orderItem->quantity) }}</p>
            <button type="submit">{{ __('Delete order') }}</button>
        </form>
        @else
        <p> {{ trans_choice('futtertrog.portions_ordered', $orderItem->quantity) }}</p>
    @endcan()
@else
    @can('create', [App\OrderItem::class, $requestedDate])
        <form action="{{ route('order_items.store') }}" method="post">
            @csrf
            <input type="hidden" name="date" value="{{ $requestedDate->toDateString() }}"/>
            <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
            <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>

            <label for="amount-{{ $meal->id }}" class="sr-only">{{ __('Amount') }}</label>
            <input type="number" name="quantity" min="1" value="1" id="amount-{{ $meal->id }}">
            <button type="submit">{{ __('Place order') }}</button>
        </form>
    @endcan
@endif

