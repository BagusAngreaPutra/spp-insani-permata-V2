<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Top Up · Permata Insani</title>
    <link href="{{ asset('css/permata-student-dashboard.css') }}?v=20260813-2" rel="stylesheet">
</head>
<body>
    @include('layouts.sidebar-siswa')
    <div class="main-content">
        @include('layouts.header-siswa')
        <div class="dashboard-content"><div class="content-container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Status top up</h1>
                <p class="dashboard-subtitle">{{ $topup->reference }}</p>
            </div>
            <section class="dashboard-card"><div class="card-content">
                <p><strong>Nominal:</strong> Rp{{ number_format((float) $topup->amount, 0, ',', '.') }}</p>
                <p><strong>Status:</strong> {{ ucfirst($topup->status) }}</p>
                @if($topup->status === 'pending')
                    <p>Menunggu pembayaran dan verifikasi gateway. Halaman ini aman untuk ditutup.</p>
                @elseif($topup->status === 'paid')
                    <p class="text-success">Saldo sudah ditambahkan ke dompet.</p>
                @elseif(in_array($topup->status, ['failed', 'expired'], true))
                    <p class="text-danger">Top up tidak berhasil. Silakan buat permintaan baru.</p>
                @endif
                <a class="btn btn-outline-primary" href="{{ route('siswa.dompet.index') }}">Kembali ke dompet</a>
            </div></section>
        </div></div>
    </div>
</body>
</html>
