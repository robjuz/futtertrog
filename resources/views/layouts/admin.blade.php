@extends('layouts.app')

@section('navigation')
    <nav id="main-navbar" class="admin-navigation" data-button="{{ __('Menu') }}" aria-label="{{ __('Administration') }}">
        <ul>
            <li><a href="{{ route('filament.administration.pages.dashboard') }}">{{ __('Administration') }}</a></li>
            <li><a href="{{ route('users.index') }}">{{ __('Manage users') }}</a></li>
            <li><a href="{{ route('admin.meals.index') }}">{{ __('Manage meals') }}</a></li>
            <li><a href="{{ route('meals.create') }}">{{ __('New meal') }}</a></li>
            <li><a href="{{ route('orders.index') }}">{{ __('Manage orders') }}</a></li>
            <li><a href="{{ route('admin.order_items.create') }}">{{ __('Create order') }}</a></li>
            <li><a href="{{ route('deposits.index') }}">{{ __('Manage deposits') }}</a></li>
            <li><a href="{{ route('deposits.transfer.create') }}">{{ __('Transfer') }}</a></li>
            <li><a href="{{ route('notifications.create') }}">{{ __('Send notification') }}</a></li>
            <li><a href="{{ route('home') }}">{{ __('Back to ordering') }}</a></li>
            <li>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit">{{ __('Logout') }}</button>
                </form>
            </li>
        </ul>
    </nav>
@endsection
