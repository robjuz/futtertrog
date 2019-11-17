<nav class="user-menu">

    <a href="{{ route('users.create') }}"
        {{ request()->routeIs('users.create') ? ' aria-current="page"' : '' }}
    >
        {{ __('New user') }}
    </a>

    <a
        href="{{ route('deposits.create' )}}"
        {{ request()->routeIs('deposits.create') ? ' aria-current="page"' : '' }}
    >
        {{ __('New deposit') }}
    </a>

    <a
        href="{{ route('deposits.transfer' )}}"
        {{ request()->routeIs('deposits.transfer') ? ' aria-current="page"' : '' }}
    >
        {{ __('New money transfer') }}
    </a>

    <a href="{{ route('notifications.create' )}}"
        {{ request()->routeIs('notifications.create') ? ' aria-current="page"' : '' }}
    >
        {{ __('New notification') }}
    </a>
</nav>

