<header class="site-navbar">
    <div class="navbar-left">
        <button type="button" class="icon-button" id="sidebarToggle" aria-label="Buka atau tutup sidebar" aria-controls="appSidebar" aria-expanded="true">
            <span class="hamburger"><span></span><span></span><span></span></span>
        </button>
        <a href="{{ route('dashboard') }}" class="brand-link" aria-label="PAR Mobile Dashboard">
            <img class="brand-logo" src="{{ asset('images/DOWA-LOGO.png') }}" alt="DOWA logo">
            <span class="brand-name">PAR MOBILE</span>
        </a>
    </div>

    <div class="navbar-right">
        <span class="navbar-user"><span class="user-icon">●</span>{{ auth()->user()->username ?? auth()->user()->name ?? 'User' }}</span>
        <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Yakin ingin keluar?')">
            @csrf
            <button type="submit" class="navbar-logout"><span aria-hidden="true">↪</span> Keluar</button>
        </form>
    </div>
</header>
