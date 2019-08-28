
<h4>
    {{ $meal->title }}
</h4>

<small>{{ number_format($meal->price, 2, ',', '.') }} €</small>

@if (!(auth()->user()->settings[\App\User::SETTING_HIDE_ORDERING_MEAL_DESCRIPTION] ?? false))
    <p>{{ $meal->description }}</p>
@endif

@can('update', $meal)
    <a href="{{ route('meals.edit', $meal) }}">
        {{ __('Edit') }}
    </a>
@endcan

@can('delete', $meal)
    <form action="{{ route('meals.destroy', $meal) }}" method="post">
        @method('delete')
        @csrf
        <button type="submit">{{ __('Delete') }}</button>
    </form>
@endcan


@if($orderItem = $todayOrders->firstWhere('meal_id', $meal->id))
    <form onsubmit="toggleOrder(event)" action="{{ route('order_items.destroy', $orderItem) }}" method="post">
        @csrf
        @method('delete')
        <button type="submit">{{ __('Delete order') }}</button>
    </form>
@else
   <form onsubmit="toggleOrder(event)" action="{{ route('order_items.store') }}" method="post">
        @csrf
        <input type="hidden" name="date" value="{{ $requestedDate->toDateString() }}"/>
        <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
        <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>

        <label for="amount-{{ $meal->id }}" class="sr-only">{{ __('Amount') }}</label>
        <input type="number" name="quantity" min="1" value="1" id="amount-{{ $meal->id }}">
        <button type="submit">{{ __('Place order') }}</button>
    </form>
@endif

