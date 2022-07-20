<li id="meal_{{ $meal->id }}"
    class="meal {{ $meal->isOrdered() ? ' selected' : '' }}"
>
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

    @if($meal->variants->isEmpty())
        <small class="money">{{ $meal->price }}</small>
    @endif

    @if($meal->image)
        <div>
            <img src="{{ $meal->image }}" alt="">
        </div>
    @endif

    @if (!(auth()->user()->settings->hideOrderingMealDescription ?? false))
        @if ($meal->description)
            <p>{!! $meal->description !!}</p>
        @endif
    @endif

    @if($meal->info->isNotEmpty())
        <hr>
        @if($meal->info->calories)
            <p>{{ __('futtertrog.calories', ['calories' => $meal->info->calories]) }}</p>
        @endif

        @if($meal->info->allergens)
            <details>
                <summary>{{ __('Allergens') }}</summary>
                @foreach($meal->info->allergens as $allergen)
                    {{ $allergen }}<br>
                @endforeach
            </details>
        @endif
    @endif


    @if($meal->isOrdered())

        @can('delete', $meal->orderItem())
            <form action="{{ route('order_items.update', $meal->orderItem()) }}" method="post" class="meal-form">
                @csrf
                @method('put')
                <input type="hidden" name="quantity" value="0">
                <input type="hidden" name="meal_id" value="{{ $meal->orderItem()->meal_id }}"/>
                <p> {{ trans_choice('futtertrog.portions_ordered', $meal->orderItem()->quantity) }}</p>
                <button type="submit">{{ __('Delete order') }}</button>
            </form>
        @else
            <p> {{ trans_choice('futtertrog.portions_ordered', $meal->orderItem()->quantity) }}</p>
        @endcan()
    @else
        <form action="{{ route('order_items.store') }}" method="post" class="meal-form">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->id() }}"/>
            @if($meal->variants->isEmpty())
                <input type="hidden" name="meal_id" value="{{ $meal->id }}"/>
            @else
                <fieldset class="variants">
                    <legend>{{ __('Variants') }}</legend>
                    @foreach($meal->variants as $variant)
                        <input type="radio" name="meal_id" value="{{ $variant->id }}" id="variant_{{ $variant->id }}"
                               @if($loop->first) checked @endif />
                        <label for="variant_{{ $variant->id }}">
                            <span>{{ $variant->variant_title }}</span>
                            <small class="money">{{ $variant->price }}</small>
                        </label>
                    @endforeach
                </fieldset>
            @endif
            @can('create', [App\OrderItem::class, $meal->date_from])
                <label for="amount-{{ $meal->id }}" class="sr-only">{{ __('Amount') }}</label>
                <input type="number" name="quantity" min="1" value="1" id="amount-{{ $meal->id }}">
                <button type="submit">{{ __('Place order') }} </button>
            @endcan
        </form>
    @endif
</li>
