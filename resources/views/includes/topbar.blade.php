<nav class="navbar navbar-dark bg-dark topbar fixed-top">
    <div class="container-fluid px-3 justify-content-start">
        <button id="sidebarToggle" class="topbar-burger me-3" type="button" aria-label="Toggle menu">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <a href="{{ route('home.navigate', ['page' => 'dashboard']) }}" class="navbar-brand topbar-brand d-flex align-items-center mb-0">
            <img src="{{ asset('assets/images/icon_fbss.png') }}" alt="FBSS" height="30" class="me-2">
            <span>FBSS <span class="topbar-brand-light">Portal</span></span>
        </a>
    </div>
</nav>