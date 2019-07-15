<div class="card mb-3">
    <h2 class="card-header">{{ __('Balance') }}</h2>

    <div class="card-body">
        <span class="{{  $user->balance > 0 ? 'text-success' : 'text-danger' }}">
            {{ number_format( $user->balance, 2, ',','.') }} €
        </span>
    </div>
</div>
