<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $adminUser = Auth::guard('web')->user();
    $canMaster = $adminUser?->hasAnyPermission(['sekolah.manage', 'tahun_ajaran.manage', 'kelas.manage', 'siswa.manage', 'admin.manage', 'jenis_pembayaran.manage']) ?? false;
    $canTagihan = $adminUser?->hasAnyPermission(['tagihan.manage', 'pembayaran.process']) ?? false;
    $canJenisTagihan = $adminUser?->hasPermission('jenis_pembayaran.manage') ?? false;
    $canRiwayat = $adminUser?->hasPermission('riwayat.view') ?? false;
    $canKoperasi = $adminUser?->hasAnyPermission(['koperasi.barang.manage', 'koperasi.stok.manage', 'koperasi.penjualan.manage']) ?? false;
    $canPemasukan = $canTagihan || $canKoperasi || ($adminUser?->hasPermission('pemasukan.manage') ?? false);
    $canKenaikan = $adminUser?->hasAnyPermission(['kenaikan.manage', 'kelulusan.manage']) ?? false;
    $canTools = $adminUser?->hasAnyPermission(['tagihan.manage', 'log.view', 'import_excel.manage', 'export_excel.manage', 'backup.manage']) ?? false;
    $canLaporan = $adminUser?->hasPermission('laporan.view') ?? false;
    $adminInitial = strtoupper(substr($adminUser?->nama_admin ?? 'G', 0, 1));
    $adminHomeUrl = $canTagihan
        ? route('tagihan.index.grouped')
        : ($canRiwayat ? route('riwayat.index') : route('dashboard'));
@endphp

<div class="sidebar-overlay-bg" id="sidebarOverlayBg" onclick="toggleSidebarMenu()"></div>

<aside class="app-sidebar" id="appSidebar">
    <div class="app-sidebar-head">
        <a class="app-brand" href="{{ $adminHomeUrl }}">
            <img src="{{ asset('images/logo.jpg') }}" alt="Logo Permata Insani">
            <span>
                <strong>Permata Insani</strong>
                <small>Administrasi sekolah</small>
            </span>
        </a>

        <label class="app-nav-search" for="appNavSearch">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <input id="appNavSearch" type="search" placeholder="Cari menu..." autocomplete="off">
            <kbd>/</kbd>
        </label>
    </div>

    <div class="app-nav-scroll">
        <section class="app-nav-section" data-nav-section>
            <p class="app-nav-label">Menu utama</p>
            <nav class="app-nav" aria-label="Menu utama">
                @if($canPemasukan)
                    <details class="app-nav-group" {{ request()->routeIs('tagihan.*', 'koperasi.*', 'pemasukan.*') ? 'open' : '' }} data-nav-item>
                        <summary><i class="fas fa-arrow-trend-up"></i><span>Pemasukan</span><i class="fas fa-chevron-down"></i></summary>
                        <div>
                            @if($canTagihan)
                                <a href="{{ route('tagihan.index.grouped') }}" class="{{ request()->routeIs('tagihan.*') ? 'is-active' : '' }}"><i class="fas fa-receipt"></i><span>Tagihan Siswa</span></a>
                            @endif
                            @if($canKoperasi)
                                <a href="{{ $adminUser->hasPermission('koperasi.penjualan.manage') ? route('koperasi.penjualan.index') : route('koperasi.index') }}" class="{{ request()->routeIs('koperasi.*') ? 'is-active' : '' }}"><i class="fas fa-store"></i><span>Koperasi</span></a>
                            @endif
                            @if($adminUser->hasPermission('pemasukan.manage'))
                                <a href="{{ route('pemasukan.index') }}" class="{{ request()->routeIs('pemasukan.*') ? 'is-active' : '' }}"><i class="fas fa-coins"></i><span>Pemasukan Lainnya</span></a>
                            @endif
                        </div>
                    </details>
                @endif

                @if($canMaster)
                    <details class="app-nav-group" {{ request()->routeIs('sekolah.*', 'tahun_ajaran.*', 'kelas.*', 'siswa.*', 'admin.*', 'jenis_pembayaran.*') ? 'open' : '' }} data-nav-item>
                        <summary><i class="fas fa-building-columns"></i><span>Data sekolah</span><i class="fas fa-chevron-down"></i></summary>
                        <div>
                            @if($adminUser->hasAnyPermission(['sekolah.manage', 'kelas.manage']))
                                <a href="{{ route('sekolah.index') }}" class="{{ request()->routeIs('sekolah.*', 'kelas.*') ? 'is-active' : '' }}"><i class="fas fa-school"></i><span>Sekolah & kelas</span></a>
                            @endif
                            @if($adminUser->hasPermission('tahun_ajaran.manage'))
                                <a href="{{ route('tahun_ajaran.index') }}" class="{{ request()->routeIs('tahun_ajaran.*') ? 'is-active' : '' }}"><i class="fas fa-calendar-days"></i><span>Tahun ajaran</span></a>
                            @endif
                            @if($adminUser->hasPermission('siswa.manage'))
                                <a href="{{ route('siswa.index') }}" class="{{ request()->routeIs('siswa.*') ? 'is-active' : '' }}"><i class="fas fa-user-graduate"></i><span>Siswa</span></a>
                            @endif
                            @if($adminUser->hasPermission('admin.manage'))
                                <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.*') ? 'is-active' : '' }}"><i class="fas fa-user-tie"></i><span>Guru & admin</span></a>
                            @endif
                            @if($canJenisTagihan)
                                <a href="{{ route('jenis_pembayaran.index') }}" class="{{ request()->routeIs('jenis_pembayaran.*') ? 'is-active' : '' }}"><i class="fas fa-tags"></i><span>Jenis pembayaran</span></a>
                            @endif
                        </div>
                    </details>
                @endif

                @if($canRiwayat)
                    <a href="{{ route('riwayat.index') }}" class="app-nav-link {{ request()->routeIs('riwayat.*') ? 'is-active' : '' }}" data-nav-item>
                        <i class="fas fa-clock-rotate-left"></i><span>Riwayat Transaksi</span>
                    </a>
                @endif

                @if($canKenaikan)
                    <details class="app-nav-group" {{ request()->routeIs('kenaikan.*', 'kelulusan.*') ? 'open' : '' }} data-nav-item>
                        <summary><i class="fas fa-graduation-cap"></i><span>Akademik</span><i class="fas fa-chevron-down"></i></summary>
                        <div>
                            @if($adminUser->hasPermission('kenaikan.manage'))
                                <a href="{{ route('kenaikan.index') }}" class="{{ request()->routeIs('kenaikan.*') ? 'is-active' : '' }}"><i class="fas fa-arrow-up-right-dots"></i><span>Kenaikan kelas</span></a>
                            @endif
                            @if($adminUser->hasPermission('kelulusan.manage'))
                                <a href="{{ route('kelulusan.index') }}" class="{{ request()->routeIs('kelulusan.*') ? 'is-active' : '' }}"><i class="fas fa-medal"></i><span>Kelulusan</span></a>
                            @endif
                        </div>
                    </details>
                @endif
            </nav>
        </section>

        @if($canLaporan)
            <section class="app-nav-section" data-nav-section>
                <p class="app-nav-label">Laporan</p>
                <nav class="app-nav" aria-label="Laporan">
                    <details class="app-nav-group app-report-group" {{ request()->routeIs('laporan.*') ? 'open' : '' }} data-nav-item>
                        <summary><i class="fas fa-chart-column"></i><span>Laporan Transaksi</span><i class="fas fa-chevron-down"></i></summary>
                        <div class="app-report-panel">
                            <a href="{{ route('laporan.transaksi') }}" class="{{ request()->routeIs('laporan.transaksi*') ? 'is-active' : '' }}"><i class="fas fa-chart-line"></i><span>Semua Transaksi</span></a>
                            <a href="{{ route('laporan.tagihan_siswa') }}" class="{{ request()->routeIs('laporan.tagihan_siswa*') ? 'is-active' : '' }}"><i class="fas fa-file-invoice-dollar"></i><span>Tagihan Siswa</span></a>
                            <a href="{{ route('laporan.koperasi') }}" class="{{ request()->routeIs('laporan.koperasi*') ? 'is-active' : '' }}"><i class="fas fa-store"></i><span>Koperasi</span></a>
                            <a href="{{ route('laporan.pemasukan') }}" class="{{ request()->routeIs('laporan.pemasukan*') ? 'is-active' : '' }}"><i class="fas fa-coins"></i><span>Pemasukan Lainnya</span></a>
                        </div>
                    </details>
                </nav>
            </section>
        @endif

        @if($canTools)
            <section class="app-nav-section" data-nav-section>
                <p class="app-nav-label">Sistem</p>
                <nav class="app-nav" aria-label="Sistem">
                    <details class="app-nav-group" {{ request()->routeIs('pengaturan.*', 'log_aktivitas.*', 'import.*', 'export_excel.*', 'backup.*') ? 'open' : '' }} data-nav-item>
                        <summary><i class="fas fa-sliders"></i><span>Pengaturan data</span><i class="fas fa-chevron-down"></i></summary>
                        <div>
                            @if($adminUser->hasPermission('tagihan.manage'))
                                <a href="{{ route('pengaturan.diskon-spp.edit') }}" class="{{ request()->routeIs('pengaturan.diskon-spp.*') ? 'is-active' : '' }}"><i class="fas fa-percent"></i><span>Diskon SPP</span></a>
                            @endif
                            @if($adminUser->hasPermission('log.view'))
                                <a href="{{ route('log_aktivitas.index') }}" class="{{ request()->routeIs('log_aktivitas.*') ? 'is-active' : '' }}"><i class="fas fa-clock-rotate-left"></i><span>Aktivitas</span></a>
                            @endif
                            @if($adminUser->hasPermission('import_excel.manage'))
                                <a href="{{ route('import.form') }}" class="{{ request()->routeIs('import.*') ? 'is-active' : '' }}"><i class="fas fa-file-import"></i><span>Import data</span></a>
                            @endif
                            @if($adminUser->hasPermission('export_excel.manage'))
                                <a href="{{ route('export_excel.index') }}" class="{{ request()->routeIs('export_excel.*') ? 'is-active' : '' }}"><i class="fas fa-file-export"></i><span>Export data</span></a>
                            @endif
                            @if($adminUser->hasPermission('backup.manage'))
                                <a href="{{ route('backup.index') }}" class="{{ request()->routeIs('backup.*') ? 'is-active' : '' }}"><i class="fas fa-database"></i><span>Backup</span></a>
                            @endif
                        </div>
                    </details>
                </nav>
            </section>
        @endif

        <p class="app-nav-empty" id="appNavEmpty" hidden>Menu tidak ditemukan.</p>
    </div>

    <div class="app-sidebar-footer">
        <a class="app-profile-card {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.edit') }}">
            <span class="app-user-avatar">{{ $adminInitial }}</span>
            <span class="app-profile-copy">
                <strong>{{ $adminUser?->nama_admin ?? 'Guru' }}</strong>
                <small>Guru</small>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="app-sidebar-logout" type="submit" title="Keluar" aria-label="Keluar">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (() => {
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('sidebarOverlayBg');
        const search = document.getElementById('appNavSearch');
        const emptyState = document.getElementById('appNavEmpty');

        window.toggleSidebarMenu = function () {
            sidebar?.classList.toggle('is-open');
            overlay?.classList.toggle('is-active');
        };

        if (!sidebar || !search) return;

        const groups = [...sidebar.querySelectorAll('.app-nav-group')];
        groups.forEach((group) => {
            group.dataset.initiallyOpen = group.open ? 'true' : 'false';
            group.addEventListener('toggle', () => {
                if (!search.value.trim()) {
                    group.dataset.initiallyOpen = group.open ? 'true' : 'false';
                }
            });
        });

        const filterNavigation = () => {
            const query = search.value.trim().toLocaleLowerCase('id-ID');
            let visibleCount = 0;

            sidebar.querySelectorAll('[data-nav-item]').forEach((item) => {
                const matches = !query || item.textContent.toLocaleLowerCase('id-ID').includes(query);
                item.hidden = !matches;

                if (matches) {
                    visibleCount++;
                    if (query && item.tagName === 'DETAILS') item.open = true;
                }
            });

            groups.forEach((group) => {
                if (!query) group.open = group.dataset.initiallyOpen === 'true';
            });

            sidebar.querySelectorAll('[data-nav-section]').forEach((section) => {
                const hasVisibleItem = [...section.querySelectorAll('[data-nav-item]')].some((item) => !item.hidden);
                section.hidden = !hasVisibleItem;
            });

            emptyState.hidden = visibleCount !== 0;
        };

        search.addEventListener('input', filterNavigation);

        document.addEventListener('keydown', (event) => {
            if (event.key === '/' && !/input|textarea|select/i.test(document.activeElement?.tagName ?? '')) {
                event.preventDefault();
                search.focus();
            }
        });
    })();
</script>
