<div class="card">
    <div class="card-header">
        {{ __('Actions') }}
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="{{ route('users.create') }}"
               class="btn btn-link {{ request()->routeIs('users.create') ? ' active' : '' }}">
                {{ __('New user') }}
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('deposits.create' )}}"
               class="btn btn-link {{ request()->routeIs('deposits.create') ? ' active' : '' }}">
                {{ __('New deposit') }}
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('deposits.transfer' )}}"
               class="btn btn-link {{ request()->routeIs('deposits.transfer') ? ' active' : '' }}">
                {{ __('New money transfer') }}
            </a>
        </li>
    </ul>
</div>
