@php
    $tabModule = $module ?? 'sekolah';
    $tabActive = $active ?? 'data';
    $tabUser = Auth::guard('web')->user();
    $canOpenHistory = $tabUser?->hasPermission('riwayat.view') ?? false;
    $canOpenData = match($tabModule) {
        'sekolah' => $tabUser?->hasAnyPermission(['tagihan.manage', 'pembayaran.process']) ?? false,
        'koperasi' => $tabUser?->hasPermission('koperasi.penjualan.manage') ?? false,
        'pemasukan' => $tabUser?->hasPermission('pemasukan.manage') ?? false,
        default => false,
    };
    $tabConfig = [
        'sekolah' => [
            'data_label' => 'Tagihan Siswa',
            'history_label' => 'Riwayat Pembayaran',
            'data_url' => route('tagihan.index.grouped'),
        ],
        'koperasi' => [
            'data_label' => 'Transaksi Koperasi',
            'history_label' => 'Riwayat Koperasi',
            'data_url' => route('koperasi.penjualan.index'),
        ],
        'pemasukan' => [
            'data_label' => 'Pemasukan Lainnya',
            'history_label' => 'Riwayat Pemasukan',
            'data_url' => route('pemasukan.index'),
        ],
    ][$tabModule];
@endphp

<style>
    .transaction-tabs {
        display: flex;
        align-items: flex-end;
        gap: 3px;
        width: 100%;
        margin: 0 0 22px;
        padding: 6px 8px 0;
        overflow-x: auto;
        background: #eef2f5;
        border: 1px solid #d8dee6;
        border-radius: 11px 11px 0 0;
        box-shadow: inset 0 -1px 0 #cfd6df;
    }
    .transaction-tabs a {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        min-height: 36px;
        margin-bottom: -1px;
        padding: 8px 18px 9px;
        color: #596579 !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        border-bottom-color: #cfd6df !important;
        border-radius: 9px 9px 0 0 !important;
        box-shadow: none !important;
        font-size: 11px !important;
        font-weight: 650 !important;
        text-decoration: none !important;
        white-space: nowrap;
    }
    .transaction-tabs a:hover {
        color: #175c43 !important;
        background: rgba(255, 255, 255, .58) !important;
        border-color: #d8dee6 #d8dee6 #cfd6df !important;
    }
    .transaction-tabs a.is-active {
        z-index: 1;
        color: #174f3d !important;
        background: #fff !important;
        border-color: #cfd6df #cfd6df #fff !important;
        box-shadow: 0 -2px 5px rgba(16, 24, 40, .04) !important;
        font-weight: 750 !important;
    }
    .transaction-tabs a.is-active::before {
        position: absolute;
        top: 0;
        right: 11px;
        left: 11px;
        height: 2px;
        background: #25845d;
        border-radius: 0 0 3px 3px;
        content: '';
    }
    @media (max-width: 640px) {
        .transaction-tabs { padding-right: 5px; padding-left: 5px; }
        .transaction-tabs a { min-height: 34px; padding-right: 13px; padding-left: 13px; }
    }

    /* Cegah gaya tombol global mengubah tautan navigasi ini menjadi button. */
    body:has(.app-sidebar) .main-content .transaction-tabs > a,
    body:has(.app-sidebar) .main-content .transaction-tabs > a:link,
    body:has(.app-sidebar) .main-content .transaction-tabs > a:visited {
        min-height: 36px !important;
        padding: 8px 18px 9px !important;
        color: #596579 !important;
        background: transparent !important;
        border: 1px solid transparent !important;
        border-bottom-color: #cfd6df !important;
        border-radius: 9px 9px 0 0 !important;
        box-shadow: none !important;
    }
    body:has(.app-sidebar) .main-content .transaction-tabs > a:hover {
        color: #175c43 !important;
        background: rgba(255, 255, 255, .58) !important;
        border-color: #d8dee6 #d8dee6 #cfd6df !important;
        box-shadow: none !important;
        transform: none !important;
    }
    body:has(.app-sidebar) .main-content .transaction-tabs > a.is-active {
        color: #174f3d !important;
        background: #fff !important;
        border-color: #cfd6df #cfd6df #fff !important;
        box-shadow: 0 -2px 5px rgba(16, 24, 40, .04) !important;
        transform: none !important;
    }
</style>

<nav class="transaction-tabs" aria-label="Navigasi {{ $tabConfig['data_label'] }}">
    @if($canOpenData)
    <a href="{{ $tabConfig['data_url'] }}" class="{{ $tabActive === 'data' ? 'is-active' : '' }}">
        <span>{{ $tabConfig['data_label'] }}</span>
    </a>
    @endif
    @if($canOpenHistory)
    <a href="{{ route('riwayat.index', ['jenis_pembayaran' => $tabModule]) }}" class="{{ $tabActive === 'history' ? 'is-active' : '' }}">
        <span>{{ $tabConfig['history_label'] }}</span>
    </a>
    @endif
</nav>
