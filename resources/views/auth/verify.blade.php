@extends('layouts.app')

@section('content')

    <h1>{{ __('Verify Your Email Address') }}</h1>

        @if (session('resent'))
            <p>
                {{ __('A fresh verification link has been sent to your email address.') }}
            </p>
        @endif

    <p>
        {{ __('Before proceeding, please check your email for a verification link.') }}
        {{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>.
    </p>

@endsection
