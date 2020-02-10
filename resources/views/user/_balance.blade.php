<h2>{{ __('Balance') }}</h2>

<p class="{{  $balance > 0 ? 'positive-value' : 'negative-value' }}">
    @money($balance)
</span>
