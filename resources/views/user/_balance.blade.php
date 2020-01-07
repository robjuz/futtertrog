<h2>{{ __('Balance') }}</h2>

<p class="{{  $balance > 0 ? 'text-success' : 'text-danger' }}">
    {{ number_format(0.01 * $balance, 2, ',','.') }} €
</span>
