<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keuangan Siswa - Permata Insani</title>
    <link href="{{ asset('css/permata-student-dashboard.css') }}?v=20260813-2" rel="stylesheet">
    <link href="{{ asset('css/wallet-pages.css') }}?v=20261114-9" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="wallet-page">
    @include('layouts.sidebar-siswa')
    <div class="main-content">
        @include('layouts.header-siswa')
        <main class="dashboard-content"><div class="content-container">
            <header class="dashboard-header">
                <div><span class="wallet-eyebrow">PORTAL KEUANGAN</span><h1 class="dashboard-title">Keuangan siswa</h1><p class="dashboard-subtitle">Saldo, tagihan, dan aktivitas pembayaran dalam satu tampilan.</p></div>
            </header>

            @if(session('success'))<div class="wallet-alert wallet-alert-success"><i class="fas fa-circle-check"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="wallet-alert wallet-alert-danger"><i class="fas fa-triangle-exclamation"></i>{{ $errors->first() }}</div>@endif

            <div class="wallet-priority-strip" aria-label="Ringkasan kewajiban">
                <div class="priority-item"><span class="priority-icon priority-icon-warning"><i class="fas fa-receipt"></i></span><div><small>Total perlu dibayar</small><strong>Rp{{ number_format((float) $totalDue, 0, ',', '.') }}</strong></div></div>
                <div class="priority-divider"></div>
                <div class="priority-item"><span class="priority-icon priority-icon-blue"><i class="fas fa-calendar-day"></i></span><div><small>Jatuh tempo terdekat</small><strong>{{ $nextDue ? ($nextDue['bill']->tanggal_jatuh_tempo?->format('d M Y') ?? 'Belum ditentukan') : 'Tidak ada tagihan' }}</strong></div></div>
                <div class="priority-spacer"></div><a class="priority-link" href="#tagihan-section">Lihat tagihan <i class="fas fa-arrow-right"></i></a>
            </div>

            <section class="wallet-top-section" aria-labelledby="wallet-title">
                <div class="wallet-hero">
                    <div class="wallet-card-top"><span class="wallet-chip"><i class="fas fa-wallet"></i> DOMPET SISWA</span><span class="wallet-card-id">ID {{ str_pad((string) $account->id, 4, '0', STR_PAD_LEFT) }}</span></div>
                    <div class="wallet-hero-label" id="wallet-title">Saldo tersedia</div>
                    <div class="wallet-hero-value">Rp{{ number_format((float) $account->available_balance, 0, ',', '.') }}</div>
                    <div class="wallet-hero-bottom"><span class="wallet-hero-note"><i class="fas fa-shield-halved"></i> Saldo aman dan tercatat otomatis</span><a class="btn btn-light" href="#topup-form"><i class="fas fa-plus-circle me-1"></i> Isi saldo</a></div>
                </div>

                <div class="wallet-overview-row">
                    <div class="wallet-quick-actions">
                        <a href="#topup-form" class="wallet-quick-action"><span class="quick-icon quick-icon-green"><i class="fas fa-arrow-down-to-bracket"></i></span><span><strong>Isi saldo</strong><small>Tambah saldo dompet</small></span><i class="fas fa-chevron-right"></i></a>
                        <a href="#tagihan-section" class="wallet-quick-action"><span class="quick-icon quick-icon-blue"><i class="fas fa-receipt"></i></span><span><strong>Bayar tagihan</strong><small>SPP dan keperluan sekolah</small></span><i class="fas fa-chevron-right"></i></a>
                        <a href="#aktivitas-section" class="wallet-quick-action"><span class="quick-icon quick-icon-purple"><i class="fas fa-clock-rotate-left"></i></span><span><strong>Aktivitas</strong><small>Transaksi terbaru</small></span><i class="fas fa-chevron-right"></i></a>
                    </div>
                    <div class="wallet-mini-stats">
                        <article class="stat-card"><div class="stat-icon"><i class="fas fa-lock"></i></div><div><div class="stat-label">Saldo ditahan</div><div class="stat-value">Rp{{ number_format((float) $account->held_balance, 0, ',', '.') }}</div><div class="stat-caption">Belum dapat digunakan</div></div></article>
                        <article class="stat-card"><div class="stat-icon stat-icon-soft"><i class="fas fa-circle-check"></i></div><div><div class="stat-label">Status dompet</div><div class="stat-value stat-value-small">Aktif</div><div class="stat-caption">Siap untuk pembayaran</div></div></article>
                    </div>
                </div>

                <div class="wallet-topup-bar" id="topup-form">
                    <div class="wallet-topup-copy"><span class="wallet-topup-icon"><i class="fas fa-circle-plus"></i></span><div><strong>Isi saldo dompet</strong><small>Gunakan gateway pembayaran untuk menambah saldo.</small></div></div>
                    <form method="POST" action="{{ route('siswa.dompet.topup.store') }}" class="wallet-form">
                        @csrf
                        <label><span class="sr-only">Nominal isi saldo</span><input id="topupAmount" type="number" name="amount" min="10000" max="5000000" step="1000" required class="form-control" placeholder="Nominal, minimal Rp10.000" value="{{ old('amount') }}"><span class="topup-presets"><button type="button" data-amount="25000">Rp25rb</button><button type="button" data-amount="50000">Rp50rb</button><button type="button" data-amount="100000">Rp100rb</button><button type="button" data-amount="250000">Rp250rb</button></span></label>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Lanjutkan</button>
                    </form>
                </div>
                <p class="wallet-topup-note">Minimum Rp10.000 dan maksimum Rp5.000.000 per transaksi. Saldo masuk setelah pembayaran terverifikasi.</p>
            </section>

            <div class="wallet-content-grid">
                <section class="wallet-panel wallet-bills-panel" id="tagihan-section" aria-labelledby="tagihan-title">
                    <div class="wallet-panel-head"><div><span class="wallet-section-kicker">KEWAJIBAN</span><h2 class="wallet-panel-title" id="tagihan-title">Tagihan siswa</h2><p class="wallet-panel-subtitle">Tagihan dari periode {{ $selectedTahunAjaran?->label ?? 'aktif' }}.</p></div><a class="wallet-panel-link" href="{{ route('siswa.tagihan.index') }}">Kelola <i class="fas fa-arrow-right"></i></a></div>
                    <form method="POST" action="{{ route('siswa.tagihan.wallet-pay') }}" id="walletBillPaymentForm">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="bill-request-{{ \Illuminate\Support\Str::uuid() }}">
                    <div class="wallet-bill-toolbar"><span id="walletPaymentHint">Pilih tagihan yang ingin dibayar.</span><button type="submit" class="btn btn-primary" id="walletPayButton" disabled><i class="fas fa-wallet"></i> Bayar terpilih</button></div>
                    <div class="wallet-list">
                        @forelse($tagihanSaya->take(5) as $tagihan)
                            @php $terbayar = (float) $tagihan->pembayaran->sum('jumlah_bayar') + (float) $tagihan->pembayaran->sum('diskon'); $sisa = max(0, (float) $tagihan->nominal - $terbayar); @endphp
                            <div class="bill-item"><span class="bill-icon"><i class="fas fa-file-invoice"></i></span><div class="bill-copy"><strong>{{ $tagihan->nama_tagihan_dinamis }}</strong><small>{{ ucfirst($tagihan->tipe) }}{{ $tagihan->periode ? ' · '.$tagihan->periode : '' }} · Jatuh tempo {{ $tagihan->tanggal_jatuh_tempo?->format('d M Y') ?? '-' }}</small></div><div class="bill-amount"><strong>Rp{{ number_format($sisa, 0, ',', '.') }}</strong>@if($sisa <= 0)<span class="wallet-status wallet-status-paid">Lunas</span>@elseif(!$tagihan->is_active)<span class="wallet-status wallet-status-expired">Tidak aktif</span>@else<label class="bill-select"><input type="checkbox" name="tagihan_ids[]" value="{{ $tagihan->id }}" data-amount="{{ $sisa }}" class="wallet-bill-checkbox"><span class="wallet-status wallet-status-pending">Belum lunas</span></label>@endif</div></div>
                        @empty <div class="wallet-empty"><i class="fas fa-check-circle"></i><strong>Tidak ada tagihan</strong><span>Semua kewajiban pada periode aktif sudah selesai.</span></div>@endforelse
                    </div></form>
                    <div class="wallet-panel-foot"><a class="wallet-panel-link" href="{{ route('siswa.tagihan.index') }}">Lihat semua tagihan <i class="fas fa-arrow-right"></i></a></div>
                </section>

                <section class="wallet-panel wallet-activity-panel" id="aktivitas-section" aria-labelledby="aktivitas-title">
                    <div class="wallet-panel-head"><div><span class="wallet-section-kicker">AKTIVITAS</span><h2 class="wallet-panel-title" id="aktivitas-title">Riwayat terbaru</h2><p class="wallet-panel-subtitle">Pembayaran dan isi saldo terakhir.</p></div><div class="activity-head-actions"><div class="activity-filters" role="group" aria-label="Filter aktivitas"><button type="button" class="is-active" data-activity-filter="all">Semua</button><button type="button" data-activity-filter="topup">Isi saldo</button><button type="button" data-activity-filter="payment">Pembayaran</button></div><a class="wallet-panel-link" href="{{ route('siswa.riwayat.index') }}">Detail <i class="fas fa-arrow-right"></i></a></div></div>
                    <div class="wallet-list activity-list">
                        @forelse($recentActivities as $activity)
                            @php $activityType = $activity['type']; @endphp
                            <div class="activity-item" data-activity-type="{{ $activityType === 'topup' ? 'topup' : 'payment' }}"><span class="activity-icon activity-icon-{{ $activityType }}"><i class="fas {{ $activityType === 'topup' ? 'fa-arrow-down' : ($activityType === 'cooperative' ? 'fa-basket-shopping' : 'fa-receipt') }}"></i></span><div class="activity-copy"><strong>{{ $activity['title'] }}</strong><small>{{ $activityType === 'topup' ? 'Isi saldo · '.ucfirst($activity['method']) : ucfirst($activity['method']) }}</small></div><div class="activity-meta"><strong>Rp{{ number_format((float) $activity['amount'], 0, ',', '.') }}</strong><small>{{ $activity['date'] ? \Illuminate\Support\Carbon::parse($activity['date'])->format('d M, H:i') : '-' }}</small></div></div>
                        @empty <div class="wallet-empty"><i class="fas fa-clock-rotate-left"></i><strong>Belum ada aktivitas</strong><span>Transaksi terbaru akan muncul di sini.</span></div>@endforelse
                    </div>
                </section>
            </div>

            <div class="wallet-info-strip"><i class="fas fa-circle-info"></i><div><strong>Dompet digital sekolah</strong><span>Gunakan saldo untuk membayar tagihan siswa dan transaksi koperasi tanpa uang tunai.</span></div></div>
        </div></main>
    </div>
    <script>
        document.querySelectorAll('.topup-presets button').forEach(function(button){button.addEventListener('click',function(){const input=document.getElementById('topupAmount');if(input){input.value=button.dataset.amount;input.focus();}});});
        const billChecks=[...document.querySelectorAll('.wallet-bill-checkbox')], payButton=document.getElementById('walletPayButton'), payHint=document.getElementById('walletPaymentHint');
        function updateBillPayment(){const selected=billChecks.filter(function(item){return item.checked;}); if(payButton){payButton.disabled=selected.length===0;} if(payHint){payHint.textContent=selected.length?selected.length+' tagihan dipilih':'Pilih tagihan yang ingin dibayar.';}}
        billChecks.forEach(function(item){item.addEventListener('change',updateBillPayment);});
        document.querySelectorAll('[data-activity-filter]').forEach(function(button){button.addEventListener('click',function(){document.querySelectorAll('[data-activity-filter]').forEach(function(item){item.classList.remove('is-active');});button.classList.add('is-active');const filter=button.dataset.activityFilter;document.querySelectorAll('[data-activity-type]').forEach(function(item){item.hidden=filter!=='all'&&item.dataset.activityType!==filter;});});});
    </script>
</body>
</html>
