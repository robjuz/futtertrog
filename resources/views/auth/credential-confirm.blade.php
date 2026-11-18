@extends('layouts.app')
@section('content')
    <h1>{{ __('profile.Anmeldemethode bestätigen') }}</h1>
    <p>{{ __('profile.Bestätigen Sie die angeforderte Änderung. Der Link ist einmalig und 15 Minuten gültig.') }}</p>
    @if ($errors->any())
        <ul role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form method="post" autocomplete="off">
        @csrf
        @if ($needsPassword)
            <label for="password">{{ __('profile.Neues Passwort (mindestens 12 Zeichen)') }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
            <label for="password_confirmation">{{ __('profile.Neues Passwort bestätigen') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
        @endif
        <button type="submit">{{ __('profile.Änderung bestätigen') }}</button>
    </form>
@endsection
