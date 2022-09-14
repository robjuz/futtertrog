<form action="{{ route('notification.disable') }}" method="post">
    @csrf
    <input type="hidden" name="date" value="{{ request('date', today()->toDateString()) }}">
    @if ($notificationEnabledThisDay)
        <button type="submit">{{ __('Disable No order for today notification') }}</button>
    @else
        @method('delete')
        <button type="submit">{{ __('Enable No order for today notification') }}</button>
    @endif
</form>
