<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ringkasan Siswa · Permata Insani</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/table-sort.css') }}?v=20261021-2" rel="stylesheet">
    <link href="{{ asset('css/permata-student-dashboard.css') }}?v=20260813-2" rel="stylesheet">
    <script src="{{ asset('js/table-sort.js') }}?v=20261021-2" defer></script>
</head>
<body>
    @include('layouts.sidebar-siswa')

    <div class="main-content">
        @include('layouts.header-siswa')

        <div class="dashboard-content">
            <div class="content-container">
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Ringkasan</h1>
                    <p class="dashboard-subtitle">Halo, {{ Auth::guard('siswa')->user()->nama ?? 'Siswa' }}.</p>
                </div>

                <div class="stats-grid">
                    <article class="stat-card">
                        <div class="stat-header"><div><div class="stat-label">Saldo dompet</div><div class="stat-value">Rp{{ number_format((float) ($wallet->available_balance ?? 0), 0, ',', '.') }}</div></div><div class="stat-icon"><i class="fas fa-wallet"></i></div></div>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('siswa.dompet.index') }}">Buka dompet</a>
                    </article>
                    <article class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total tagihan</div>
                                <div class="stat-value">Rp{{ number_format($total_tagihan ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('siswa.tagihan.index') }}">Lihat tagihan</a>
                    </article>

                    <article class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total pembayaran</div>
                                <div class="stat-value">Rp{{ number_format($total_pembayaran ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('siswa.riwayat.index') }}">Lihat riwayat</a>
                    </article>
                </div>

                <section class="dashboard-card" style="margin-bottom:1.25rem">
                    <div class="card-content"><h2 class="section-title">Aktivitas dompet terbaru</h2>
                        @forelse($wallet_payments as $walletPayment)
                            <div style="display:flex;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid #e2e8f0"><span>{{ ucfirst(str_replace('_', ' ', $walletPayment->source_type)) }}</span><strong>Rp{{ number_format((float) $walletPayment->amount, 0, ',', '.') }}</strong></div>
                        @empty
                            <p>Belum ada pembayaran melalui dompet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="dashboard-card">
                    <div class="card-content">
                        <h2 class="section-title">Pembayaran terbaru</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tagihan</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($riwayat_pembayaran as $bayar)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($bayar->tanggal_bayar)->translatedFormat('d M Y') }}</td>
                                            <td>{{ $bayar->tagihan->nama_tagihan ?? '-' }}</td>
                                            <td>Rp{{ number_format($bayar->jumlah_bayar, 0, ',', '.') }}</td>
                                            @php
                                                $paymentStatus = strtolower(str_replace('_', ' ', trim($bayar->status ?? '')));
                                                [$paymentStatusClass, $paymentStatusIcon] = match (true) {
                                                    in_array($paymentStatus, ['belum jatuh tempo', 'sebagian', 'menunggu', 'tertunda', 'pending'], true) => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'fa-clock'],
                                                    in_array($paymentStatus, ['belum lunas', 'belum bayar', 'gagal', 'dibatalkan', 'ditolak'], true) => ['bg-danger-subtle text-danger border border-danger-subtle', 'fa-circle-exclamation'],
                                                    in_array($paymentStatus, ['proses', 'diproses', 'berjalan'], true) => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'fa-arrows-rotate'],
                                                    default => ['bg-success-subtle text-success border border-success-subtle', 'fa-circle-check'],
                                                };
                                            @endphp
                                            <td><span class="badge {{ $paymentStatusClass }}"><i class="fas {{ $paymentStatusIcon }}" aria-hidden="true"></i> {{ ucfirst($paymentStatus ?: 'tercatat') }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center">Belum ada pembayaran.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/permata-print.js') }}?v=20260804-1"></script>
</body>
</html>
