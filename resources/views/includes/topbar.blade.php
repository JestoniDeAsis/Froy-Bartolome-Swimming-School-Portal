<nav class="navbar navbar-dark bg-dark topbar fixed-top">
    <div class="container-fluid">
        <button id="sidebarToggle" class="btn btn-outline-light btn-sm me-3" type="button">
            <i class="bi bi-list"></i>
        </button>

        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center mb-0">
            <img src="{{ asset('assets/images/icon_fbss.png') }}" alt="FBSS" height="32" class="me-2">
            <span class="h5 mb-0">FBSS Portal</span>
        </a>

        <span class="ms-auto text-white small">
            {{ Auth::user()->username ?? 'Guest' }}
        </span>
    </div>
</nav>