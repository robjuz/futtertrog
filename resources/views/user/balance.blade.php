<div class="card mb-3">
    <div class="card-header">{{ __('Balance') }}</div>

    <div class="card-body">
                        <span class="{{  $user->balance > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format( $user->balance, 2, ',','.') }} €
                        </span>
    </div>
</div>