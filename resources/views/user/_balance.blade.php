<h2>{{ __('Balance') }}</h2>

<span class="{{  $user->balance > 0 ? 'text-success' : 'text-danger' }}">
    {{ number_format( $user->balance, 2, ',','.') }} €
</span>
