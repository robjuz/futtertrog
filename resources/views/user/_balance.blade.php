<h2>{{ __('Balance') }}</h2>

<p class="{{  $balance > 0 ? 'text-success' : 'text-danger' }}">
    @money($balance)
</span>
