<h2>{{ __('Balance') }}</h2>

<p class="{{ money_parse($balance)->isPositive() ? 'positive-value' : 'negative-value' }}">
    {{ $balance }}
</p>
