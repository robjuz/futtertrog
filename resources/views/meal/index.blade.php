@extends('layouts.app')

@inject('orders', 'App\Repositories\OrdersRepository')

@section('content')

    <main class="meal-index">

        <h1 id="meal-index-title">@lang('Order meal for :date', ['date' => $requestedDate->format(trans('futtertrog.date_format'))])</h1>

        @include('meal.calendar')

        <section role="region"
                 id="current-offer" <?php /* keep id for skip link */ ?>
                 aria-label="meal-index-title"
        >

            @if(!empty($todayMeals))
                <ol>
                    @foreach($todayMeals as $meal)
                        <li id="meal_{{ $meal->id }}"
                            @if($todayOrders->firstWhere('meal_id', $meal->id))
                                class="selected"
                            @endif
                        >
                            @include('meal.meal')
                        </li>
                    @endforeach
                </ol>

                <a class="text-right" href="#current-offer">
                    Zurück zum Anfang der Liste
                </a>
            @else
                <p>
                    {{ __('No items found') }}
                </p>
            @endif

        </section>
    </main>
@endsection
