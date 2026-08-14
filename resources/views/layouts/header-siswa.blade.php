<header class="app-topbar">
    <button class="app-mobile-trigger" type="button" onclick="toggleSidebarMenu()" aria-label="Buka menu">
        <i class="fas fa-bars"></i>
    </button>

    <div class="app-topbar-title">
        <span class="app-topbar-context">Permata Insani</span>
        <strong>Portal Pembayaran Siswa</strong>
    </div>

    <div class="app-user">
        <span class="app-user-avatar">{{ strtoupper(substr(Auth::guard('siswa')->user()->nama ?? 'S', 0, 1)) }}</span>
        <span class="app-user-copy">
            <strong>{{ Auth::guard('siswa')->user()->nama ?? 'Siswa' }}</strong>
            <small>Siswa</small>
        </span>
        <form method="POST" action="{{ route('siswa.logout') }}">
            @csrf
            <button class="app-icon-button app-logout-button" type="submit" title="Keluar" aria-label="Keluar">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</header>
