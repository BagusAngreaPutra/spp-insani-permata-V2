<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dompet Siswa - Permata Insani</title>
    <link href="{{ asset('css/permata-student-dashboard.css') }}?v=20260813-2" rel="stylesheet">
    <link href="{{ asset('css/wallet-pages.css') }}?v=20261114-1" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="wallet-page">
    @include('layouts.sidebar-siswa')
    <div class="main-content">
        @include('layouts.header-siswa')
        <div class="dashboard-content"><div class="content-container">
            <div class="dashboard-header"><div><span class="wallet-eyebrow">KEUANGAN SISWA</span><h1 class="dashboard-title">Dompet digital</h1><p class="dashboard-subtitle">Kelola saldo dan bayar kebutuhan sekolah dalam satu tempat.</p></div></div>

            <div class="wallet-hero">
                <div class="wallet-card-top"><span class="wallet-chip"><i class="fas fa-wallet"></i> DOMPET SISWA</span><span class="wallet-card-id">ID {{ str_pad((string) $account->id, 4, '0', STR_PAD_LEFT) }}</span></div>
                <div class="wallet-hero-label">Saldo tersedia</div>
                <div class="wallet-hero-value">Rp{{ number_format((float) $account->available_balance, 0, ',', '.') }}</div>
                <div class="wallet-hero-bottom"><span class="wallet-hero-note"><i class="fas fa-shield-halved"></i> Saldo aman dan tercatat otomatis</span><a class="btn btn-light" href="#topup-form"><i class="fas fa-plus-circle me-1"></i> Isi saldo</a></div>
            </div>

            @if(session('success'))<div class="wallet-alert wallet-alert-success"><i class="fas fa-circle-check"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="wallet-alert wallet-alert-danger"><i class="fas fa-triangle-exclamation"></i>{{ $errors->first() }}</div>@endif

            <div class="wallet-quick-actions">
                <a href="#topup-form" class="wallet-quick-action"><span class="quick-icon quick-icon-green"><i class="fas fa-arrow-down-to-bracket"></i></span><span><strong>Isi saldo</strong><small>Tambah saldo dompet</small></span><i class="fas fa-chevron-right"></i></a>
                <a href="{{ route('siswa.tagihan.index') }}" class="wallet-quick-action"><span class="quick-icon quick-icon-blue"><i class="fas fa-receipt"></i></span><span><strong>Bayar tagihan</strong><small>SPP dan keperluan sekolah</small></span><i class="fas fa-chevron-right"></i></a>
                <a href="{{ route('siswa.riwayat.index') }}" class="wallet-quick-action"><span class="quick-icon quick-icon-purple"><i class="fas fa-clock-rotate-left"></i></span><span><strong>Lihat aktivitas</strong><small>Riwayat transaksi dompet</small></span><i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="stats-grid">
                <article class="stat-card"><div class="stat-icon"><i class="fas fa-lock"></i></div><div><div class="stat-label">Saldo ditahan</div><div class="stat-value">Rp{{ number_format((float) $account->held_balance, 0, ',', '.') }}</div><div class="stat-caption">Belum dapat digunakan</div></div></article>
                <article class="stat-card"><div class="stat-icon stat-icon-soft"><i class="fas fa-circle-check"></i></div><div><div class="stat-label">Status dompet</div><div class="stat-value stat-value-small">Aktif</div><div class="stat-caption">Siap untuk pembayaran</div></div></article>
            </div>

            <section class="wallet-panel" id="topup-form"><div class="wallet-panel-head"><div><h2 class="wallet-panel-title">Isi saldo</h2><p class="wallet-panel-subtitle">Pilih nominal untuk melanjutkan pembayaran.</p></div><span class="wallet-limit"><i class="fas fa-shield-halved"></i> Aman</span></div><div class="wallet-panel-body">
                <form method="POST" action="{{ route('siswa.dompet.topup.store') }}" class="wallet-form">
                    @csrf
                    <label><span class="stat-label">Nominal (Rp)</span><input type="number" name="amount" min="10000" max="5000000" step="1000" required class="form-control" placeholder="Minimal 10.000" value="{{ old('amount') }}"></label>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Lanjutkan top up</button>
                </form><div class="wallet-muted" style="margin-top:10px">Minimum Rp10.000 dan maksimum Rp5.000.000 per transaksi. Saldo masuk setelah pembayaran terverifikasi.</div>
            </div></section>

            <section class="wallet-panel"><div class="wallet-panel-head"><div><h2 class="wallet-panel-title">Riwayat top up</h2><p class="wallet-panel-subtitle">Pantau semua penambahan saldo dompet.</p></div><a class="wallet-panel-link" href="{{ route('siswa.riwayat.index') }}">Semua aktivitas <i class="fas fa-arrow-up-right-from-square"></i></a></div><div class="table-responsive"><table class="wallet-table"><thead><tr><th>Referensi</th><th>Nominal</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>
                @forelse($topups as $topup)<tr><td><a href="{{ route('siswa.dompet.topup.show', $topup) }}">{{ $topup->reference }}</a><small class="wallet-row-meta">Top up dompet</small></td><td><strong>Rp{{ number_format((float) $topup->amount, 0, ',', '.') }}</strong></td><td><span class="wallet-status wallet-status-{{ $topup->status }}">{{ $topup->status }}</span></td><td>{{ optional($topup->created_at)->format('d/m/Y H:i') }}</td></tr>
                @empty<tr><td colspan="4" class="text-center">Belum ada top up.</td></tr>@endforelse
            </tbody></table></div>{{ $topups->links() }}</section>
            <div class="wallet-info-strip"><i class="fas fa-circle-info"></i><div><strong>Dompet digital sekolah</strong><span>Gunakan saldo untuk membayar tagihan siswa dan transaksi koperasi tanpa uang tunai.</span></div></div>
        </div></div>
    </div>
</body>
</html>
