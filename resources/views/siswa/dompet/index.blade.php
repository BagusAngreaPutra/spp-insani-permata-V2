<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dompet Siswa · Permata Insani</title>
    <link href="{{ asset('css/permata-student-dashboard.css') }}?v=20260813-2" rel="stylesheet">
    <link href="{{ asset('css/wallet-pages.css') }}?v=20261112-1" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="wallet-page">
    @include('layouts.sidebar-siswa')
    <div class="main-content">
        @include('layouts.header-siswa')
        <div class="dashboard-content">
            <div class="content-container">
                <div class="dashboard-header">
                    <h1 class="dashboard-title">Dompet siswa</h1>
                    <p class="dashboard-subtitle">Isi saldo untuk membayar tagihan dan kebutuhan koperasi.</p>
                </div>

                <div class="wallet-hero">
                    <div><div class="wallet-hero-label">Saldo tersedia</div><div class="wallet-hero-value">Rp{{ number_format((float) $account->available_balance, 0, ',', '.') }}</div><div class="wallet-hero-note">Siap digunakan untuk tagihan dan koperasi sekolah.</div></div>
                    <a class="btn btn-light" href="#topup-form"><i class="fas fa-plus-circle me-1"></i> Isi saldo</a>
                </div>
                @if(session('success'))
                    <div class="wallet-alert wallet-alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="wallet-alert wallet-alert-danger">{{ $errors->first() }}</div>
                @endif

                <div class="stats-grid">
                    <article class="stat-card">
                        <div class="stat-label">Saldo tersedia</div>
                        <div class="stat-value">Rp{{ number_format((float) $account->available_balance, 0, ',', '.') }}</div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-label">Saldo ditahan</div>
                        <div class="stat-value">Rp{{ number_format((float) $account->held_balance, 0, ',', '.') }}</div>
                    </article>
                </div>

                <section class="wallet-panel" id="topup-form"><div class="wallet-panel-head"><h2 class="wallet-panel-title">Isi saldo</h2><span class="wallet-muted">Minimum Rp10.000 · Maksimum Rp5.000.000</span></div><div class="wallet-panel-body">
                        <form method="POST" action="{{ route('siswa.dompet.topup.store') }}" class="wallet-form">
                            @csrf
                            <label><span class="stat-label">Nominal (Rp)</span>
                                <input type="number" name="amount" min="10000" max="5000000" step="1000" required class="form-control" placeholder="Minimal 10.000" value="{{ old('amount') }}">
                            </label>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Lanjutkan top up</button>
                        </form><div class="wallet-muted" style="margin-top:10px">Saldo bertambah setelah pembayaran gateway terverifikasi.</div>
                    </div>
                </section>

                <section class="wallet-panel"><div class="wallet-panel-head"><h2 class="wallet-panel-title">Riwayat top up</h2></div><div class="table-responsive">
                            <table class="wallet-table">
                                <thead><tr><th>Referensi</th><th>Nominal</th><th>Status</th><th>Dibuat</th></tr></thead>
                                <tbody>
                                @forelse($topups as $topup)
                                    <tr>
                                        <td><a href="{{ route('siswa.dompet.topup.show', $topup) }}">{{ $topup->reference }}</a></td>
                                        <td>Rp{{ number_format((float) $topup->amount, 0, ',', '.') }}</td>
                                        <td><span class="wallet-status wallet-status-{{ $topup->status }}">{{ $topup->status }}</span></td>
                                        <td>{{ optional($topup->created_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">Belum ada top up.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $topups->links() }}
                </section>
            </div>
        </div>
    </div>
</body>
</html>
