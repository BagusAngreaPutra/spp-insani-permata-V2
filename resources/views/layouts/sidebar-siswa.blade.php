<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="sidebar-overlay-bg" id="sidebarOverlayBg" onclick="toggleSidebarMenu()"></div>

<aside class="app-sidebar" id="appSidebar">
    <a class="app-brand" href="{{ route('siswa.dashboard') }}">
        <img src="{{ asset('images/logo.jpg') }}" alt="">
        <span>
            <strong>Permata Insani</strong>
            <small>Portal Siswa</small>
        </span>
    </a>

    <p class="app-nav-label">Menu</p>
    <nav class="app-nav">
        <a href="{{ route('siswa.tagihan.index') }}" class="app-nav-link {{ request()->routeIs('siswa.tagihan.*') ? 'is-active' : '' }}">
            <i class="fas fa-receipt"></i><span>Tagihan</span>
        </a>
        <a href="{{ route('siswa.dompet.index') }}" class="app-nav-link {{ request()->routeIs('siswa.dompet.*') ? 'is-active' : '' }}">
            <i class="fas fa-wallet"></i><span>Dompet</span>
        </a>
        <a href="{{ route('siswa.riwayat.index') }}" class="app-nav-link {{ request()->routeIs('siswa.riwayat.*') ? 'is-active' : '' }}">
            <i class="fas fa-clock-rotate-left"></i><span>Riwayat</span>
        </a>
        <a href="{{ route('siswa.profil.index') }}" class="app-nav-link {{ request()->routeIs('siswa.profil.*') ? 'is-active' : '' }}">
            <i class="fas fa-user"></i><span>Profil</span>
        </a>
    </nav>
</aside>

<script>
    window.toggleSidebarMenu = function () {
        document.getElementById('appSidebar')?.classList.toggle('is-open');
        document.getElementById('sidebarOverlayBg')?.classList.toggle('is-active');
    };
</script>
