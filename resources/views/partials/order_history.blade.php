<h2 id="order-history">
    {{ __('Order history') }}
</h2>

@if(request()->routeIs('home') AND $orders->count())
    <a href="{{ route('meals.ical') }}">{{ __('Download as iCal') }}</a>
@endif

@if($orders)
    <table>
        <thead>
            <tr>
                <th><span class="sr-only">Anzahl</span></th>
                <th>Menü</th>
                <th class="collapsible">Bestellt</th>
                <th>Bestellt für</th>
                <th class="money collapsible">Preis</th>
                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <th>Aktionen</th>
                @endif
            </tr>
        </thead>
        @foreach ($orders as $orderItem)
            <tr>
                <td>
                    {{ $orderItem->quantity }}
                    <span aria-hidden="true">&times;</span>
                </td>

                <td>
                     {{ $orderItem->meal->title }}
                </td>

                <td class="collapsible">
                    <small>
                        {{ $orderItem->created_at->format(__('futtertrog.date_format')) }}
                    </small>
                </td>

                <td>
                    <date>
                        {{  $orderItem->date->format(__('futtertrog.date_format'))  }}
                    </date>
                </td>

                <td class="money collapsible">
                    {{ $orderItem->meal->price }}
                </td>

                @if(auth()->user()->is_admin AND !request()->routeIs('home'))
                    <td>
                        <form action="{{ route('order_items.destroy', $orderItem) }}" method="post">
                            @csrf
                            @method('delete')

                            <button type="submit">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </td>
                @endif
            </tr>
        @endforeach
    </table>
@else
    <p> {{ __('No results found') }}!</p>
@endif

{{ $orders->links() }}

