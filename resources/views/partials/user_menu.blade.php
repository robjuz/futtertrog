<nav class="user-menu">

    <a href="{{ route('users.create') }}"
        {{ request()->routeIs('users.create') ? ' aria-current="page"' : '' }}
    >
        {{ __('New user') }}
    </a>

    <a href="{{ route('notifications.create' )}}"
        {{ request()->routeIs('notifications.create') ? ' aria-current="page"' : '' }}
    >
        {{ __('New notification') }}
    </a>
</nav>

