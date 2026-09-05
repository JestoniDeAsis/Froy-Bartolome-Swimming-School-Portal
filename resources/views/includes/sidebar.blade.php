<aside id="sideMenu" class="side-menu">

    <div class="sidebar-profile d-flex align-items-center gap-2 px-3 py-3 border-bottom">
        <div class="profile-circle d-flex align-items-center justify-content-center rounded-circle bg-secondary text-white flex-shrink-0">
            {{ strtoupper(substr($profile->first_name ?? '', 0, 1) . substr($profile->last_name ?? '', 0, 1)) }}
        </div>
        <div class="profile-info overflow-hidden">
            <div class="profile-name text-truncate fw-semibold">
                {{ strtoupper($profile->last_name ?? '') }}, {{ strtoupper($profile->first_name ?? '') }}
                @if (!empty($profile->middle_name))
                    {{ strtoupper(substr($profile->middle_name, 0, 1)) }}.
                @endif
            </div>
            <div class="profile-role text-muted small text-truncate">
                {{ $roleName ?? 'No role' }}
            </div>
        </div>
    </div>

    <ul class="nav flex-column">
        @foreach ($menuItems as $key => $item)
            <li class="nav-item">
                @if ($page === $key)
                    <span class="nav-link active" aria-current="page">
                        <i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                    </span>
                @else
                    <a href="{{ route('home.navigate', ['page' => $key]) }}" class="nav-link">
                        <i class="bi {{ $item['icon'] }} me-2"></i>{{ $item['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ul>

    <ul class="nav flex-column sidebar-footer">
        <li class="nav-item border-top pt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link text-danger border-0 bg-transparent">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </li>
    </ul>
</aside>