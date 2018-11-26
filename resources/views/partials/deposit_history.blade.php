<h2>{{ __('Deposit history') }} </h2>




@if( count($deposits->items()) > 0)

<ul class="deposit-list">
    @foreach ($deposits as $deposit)
        <li class="deposit-list__item">
            <h3>@money($deposit->value)</h3>
            <span>{{ $deposit->comment }}</span>
            <small>{{ $deposit->created_at->format(__('futtertrog.date_format')) }}</small>
        </li>
    @endforeach
</ul>

    {{--
    <table>
        <thead>
            <tr>
                <th>{{__('Betrag')}}</th>
                <th>{{__('Datum')}}</th>
                <th class="collapsible">{{__('Kommentar')}}</th>
            </tr>
        </thead>
        @foreach ($deposits as $deposit)
            <tr>
                <td class="money {{ $deposit->value > 0 ? 'positive-value' : 'negative-value' }}">
                    @money($deposit->value)
                </td>
                <td>{{ $deposit->created_at->format(__('futtertrog.date_format')) }}</td>

                <td class="collapsible"> {{ $deposit->comment }} </td>
            </tr>
        @endforeach
    </table>

    --}}


@else
    <p> {{ __('No results found') }}!</p>
@endif

{{ $deposits->links() }}


