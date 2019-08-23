<nav id="admin-menu">
    <span>
        {{ __('Actions') }}
    </span>

    <ul>
        <li>
            <a href="{{ route('users.create') }}"
               class="btn btn-link {{ request()->routeIs('users.create') ? ' active' : '' }}">
                {{ __('New user') }}
            </a>
        </li>

        <li>
            <a href="{{ route('deposits.create' )}}"
               class="btn btn-link {{ request()->routeIs('deposits.create') ? ' active' : '' }}">
                {{ __('New deposit') }}
            </a>
        </li>

        <li>
            <a href="{{ route('deposits.transfer' )}}"
               class="btn btn-link {{ request()->routeIs('deposits.transfer') ? ' active' : '' }}">
                {{ __('New money transfer') }}
            </a>
        </li>

        <li>
            <a href="{{ route('notifications.create' )}}"
               class="btn btn-link {{ request()->routeIs('notifications.create') ? ' active' : '' }}">
                {{ __('New notification') }}
            </a>
        </li>
    </ul>
</nav>

