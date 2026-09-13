@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Riwayat Transaksi')

@section('content')
@php
    $adminUser = Auth::guard('web')->user();
    $canCorrectPayment = $adminUser?->hasPermission('pembayaran.process') ?? false;
    $canManageIncome = $adminUser?->hasPermission('pemasukan.manage') ?? false;
    $formatRupiah = static fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $classLabel = static function ($kelas) {
        if (!$kelas) {
            return 'Kelas belum diatur';
        }

        $name = trim((string) $kelas->nama_kelas);

        return in_array($name, ['', '-', '–'], true)
            ? $kelas->label_tingkat
            : $kelas->label_tingkat . ' · ' . $name;
    };
    $selectedSchoolName = $selectedSekolah
        ? optional($sekolahList->firstWhere('id', $selectedSekolah))->nama_sekolah
        : null;
    $selectedClassName = $selectedKelas
        ? $classLabel($kelasList->firstWhere('id', $selectedKelas))
        : null;
    $selectedTypeName = match($selectedJenisPembayaran) {
        'sekolah' => 'Tagihan siswa',
        'koperasi' => 'Koperasi',
        'pemasukan' => 'Pemasukan lainnya',
        default => null,
    };
    $filterContext = collect([
        $selectedSchoolName,
        $selectedClassName,
        $selectedTypeName,
        $startDate ? 'Mulai ' . \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') : null,
        $endDate ? 'Sampai ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') : null,
        $search ? 'Pencarian: “' . $search . '”' : null,
    ])->filter()->implode(' · ');
    $hasActiveFilters = filled($selectedSekolah)
        || filled($selectedKelas)
        || filled($selectedJenisPembayaran)
        || filled($search)
        || filled($startDate)
        || filled($endDate);
@endphp

@push('page-styles')
<style>
    .history-page,
    .history-page * {
        box-sizing: border-box;
    }

    .history-page {
        display: grid;
        min-width: 0;
        gap: 18px;
    }

    .history-heading,
    .history-heading-actions,
    .history-filter-actions,
    .history-summary,
    .history-student,
    .history-source,
    .history-detail-heading,
    .history-detail-actions,
    .history-pagination {
        display: flex;
        align-items: center;
    }

    .history-heading {
        justify-content: space-between;
        gap: 20px;
    }

    .history-eyebrow {
        margin: 0 0 3px;
        color: #98a2b3;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .history-title {
        margin: 0;
        color: #101828;
        font-size: clamp(25px, 2.6vw, 32px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.16;
    }

    .history-subtitle {
        margin: 7px 0 0;
        color: #667085;
        font-size: 12px;
    }

    .history-heading-actions,
    .history-filter-actions,
    .history-detail-actions {
        flex: 0 0 auto;
        gap: 8px;
    }

    .history-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        min-height: 38px !important;
        margin: 0 !important;
        padding: 0 13px !important;
        color: #344054 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        font: inherit !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }

    .history-button.is-primary {
        color: #fff !important;
        background: #1d6b4c !important;
        border-color: #1d6b4c !important;
    }

    .history-button.is-warning {
        color: #b54708 !important;
        background: #fffaeb !important;
        border-color: #fedf89 !important;
    }

    .history-button.is-warning:hover {
        color: #93370d !important;
        background: #fef0c7 !important;
        border-color: #fec84b !important;
    }

    .history-button.is-pdf {
        color: #b42318 !important;
        background: #fef3f2 !important;
        border-color: #fecdca !important;
    }

    .history-button.is-pdf:hover {
        color: #912018 !important;
        background: #fee4e2 !important;
        border-color: #fda29b !important;
    }

    .history-button.is-compact {
        min-height: 32px !important;
        padding: 0 10px !important;
        font-size: 10px !important;
    }

    .history-button:hover {
        color: #101828 !important;
        background: #f9fafb !important;
    }

    .history-button.is-primary:hover {
        color: #fff !important;
        background: #15533b !important;
        border-color: #15533b !important;
    }

    .history-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 11px 13px;
        color: #087451;
        background: #ecfdf3;
        border: 1px solid #abefc6;
        border-radius: 8px;
        font-size: 11px;
    }

    .history-panel {
        min-width: 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .history-panel-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 17px 18px 15px;
        border-bottom: 1px solid #e4e7ec;
    }

    .history-panel-title,
    .history-panel-context {
        margin: 0;
    }

    .history-panel-title {
        color: #101828;
        font-size: 14px;
        font-weight: 700;
    }

    .history-panel-context {
        margin-top: 4px;
        color: #667085;
        font-size: 10px;
        line-height: 1.45;
    }

    .history-filter-form {
        position: relative;
        padding: 16px 18px;
        background: #fbfcfd;
        border-bottom: 1px solid #e4e7ec;
    }

    .history-filter-grid {
        display: grid;
        grid-template-columns: minmax(180px, 1.15fr) repeat(2, minmax(150px, 1fr)) auto auto;
        align-items: end;
        gap: 10px;
    }

    .history-filter-grid > .history-reset-button {
        align-self: end;
        min-height: 38px !important;
    }

    .history-field {
        min-width: 0;
    }

    .history-field label {
        display: block;
        margin-bottom: 6px;
        color: #475467;
        font-size: 9px;
        font-weight: 650;
    }

    .history-field input,
    .history-field select {
        width: 100% !important;
        height: 38px !important;
        margin: 0 !important;
        padding: 0 10px !important;
        color: #344054 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 7px !important;
        outline: none !important;
        box-shadow: none !important;
        font: inherit !important;
        font-size: 11px !important;
    }

    .history-field input:focus,
    .history-field select:focus {
        border-color: #8fb7f5 !important;
        box-shadow: 0 0 0 3px rgba(37, 132, 93, .08) !important;
    }

    .history-filter-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
        margin-top: 11px;
        padding-top: 11px;
        border-top: 1px solid #eef0f3;
    }

    .history-filter-context {
        min-width: 0;
        margin: 0;
        overflow: hidden;
        color: #667085;
        font-size: 10px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .history-filter-form .pi-advanced-filter-panel-header {
        display: none;
    }

    .history-summary {
        justify-content: space-between;
        gap: 16px;
        padding: 12px 18px;
        color: #667085;
        background: #fff;
        border-bottom: 1px solid #e4e7ec;
        font-size: 10px;
    }

    .history-summary-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 16px;
    }

    .history-summary strong {
        color: #344054;
        font-weight: 700;
    }

    .history-summary-type {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        font-weight: 650;
    }

    .history-summary-type.is-school {
        color: #175cd3;
        background: #eff8ff;
    }

    .history-summary-type.is-cooperative {
        color: #b54708;
        background: #fffaeb;
    }

    .history-summary-type.is-income {
        color: #6941c6;
        background: #f4f3ff;
    }

    .history-list-head,
    .history-row-summary {
        display: grid;
        grid-template-columns:
            minmax(210px, 1.45fr)
            minmax(170px, 1fr)
            minmax(115px, .68fr)
            minmax(105px, .62fr)
            minmax(125px, .72fr)
            34px;
        align-items: center;
        gap: 14px;
    }

    .history-list-head {
        min-height: 35px;
        padding: 0 18px;
        color: #667085;
        background: #f8fafc;
        border-bottom: 1px solid #e4e7ec;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .history-list {
        min-width: 0;
    }

    .history-row {
        background: #fff;
        border-bottom: 1px solid #e4e7ec;
        border-left: 4px solid transparent;
    }

    .history-row.is-school {
        border-left-color: #2e90fa;
    }

    .history-row.is-cooperative {
        border-left-color: #f79009;
    }

    .history-row.is-income {
        border-left-color: #7f56d9;
    }

    .history-row:last-child {
        border-bottom: 0;
    }

    .history-row > summary {
        list-style: none;
    }

    .history-row > summary::-webkit-details-marker {
        display: none;
    }

    .history-row-summary {
        min-height: 72px;
        padding: 12px 18px;
        cursor: pointer;
        transition: background .15s ease;
    }

    .history-row-summary:hover,
    .history-row[open] > .history-row-summary {
        background: #f9fafb;
    }

    .history-row.is-school > .history-row-summary:hover,
    .history-row.is-school[open] > .history-row-summary {
        background: #f5faff;
    }

    .history-row.is-cooperative > .history-row-summary:hover,
    .history-row.is-cooperative[open] > .history-row-summary {
        background: #fffaf0;
    }

    .history-row.is-income > .history-row-summary:hover,
    .history-row.is-income[open] > .history-row-summary {
        background: #f9f5ff;
    }

    .history-student {
        min-width: 0;
        gap: 10px;
    }

    .history-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        color: #344054;
        background: #f2f4f7;
        border: 1px solid #e4e7ec;
        border-radius: 50%;
        font-size: 10px;
        font-weight: 700;
    }

    .history-student-copy,
    .history-column-copy {
        min-width: 0;
    }

    .history-student-copy strong,
    .history-student-copy span,
    .history-column-copy strong,
    .history-column-copy span {
        display: block;
    }

    .history-student-copy strong,
    .history-column-copy strong {
        overflow: hidden;
        color: #101828;
        font-size: 11px;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .history-student-copy span,
    .history-column-copy span {
        margin-top: 2px;
        overflow: hidden;
        color: #667085;
        font-size: 9px;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .history-source {
        justify-content: flex-start;
    }

    .history-source-badge {
        display: inline-flex;
        align-items: center;
        justify-self: start;
        min-height: 23px;
        padding: 0 8px;
        color: #344054;
        background: #f2f4f7;
        border-radius: 999px;
        font-size: 8.5px;
        font-weight: 650;
        white-space: nowrap;
    }

    .history-source-badge.is-school {
        color: #175cd3;
        background: #eff8ff;
    }

    .history-source-badge.is-cooperative {
        color: #b54708;
        background: #fffaeb;
    }

    .history-source-badge.is-income {
        color: #6941c6;
        background: #f4f3ff;
    }

    .history-total {
        color: #101828;
        font-size: 11.5px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        text-align: right;
        white-space: nowrap;
    }

    .history-toggle {
        display: grid;
        place-items: center;
        width: 28px;
        height: 28px;
        margin-left: auto;
        color: #667085;
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 6px;
        font-size: 9px;
    }

    .history-toggle i {
        transition: transform .18s ease;
    }

    .history-row[open] .history-toggle i {
        transform: rotate(180deg);
    }

    .history-detail {
        padding: 15px 18px 17px;
        background: #fbfcfd;
        border-top: 1px solid #e4e7ec;
    }

    .history-detail-heading {
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 10px;
    }

    .history-detail-title {
        margin: 0;
        color: #344054;
        font-size: 10.5px;
        font-weight: 700;
    }

    .history-detail-caption {
        display: block;
        margin-top: 2px;
        color: #667085;
        font-size: 8.5px;
    }

    .history-detail-table-wrap {
        width: 100%;
        overflow-x: auto;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 7px;
    }

    .history-detail-table {
        width: 100% !important;
        min-width: 650px !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .history-detail-table th {
        height: 34px !important;
        padding: 7px 10px !important;
        color: #667085 !important;
        background: #f8fafc !important;
        border-bottom: 1px solid #e4e7ec !important;
        font-size: 8px !important;
        font-weight: 700 !important;
        text-align: left !important;
        text-transform: uppercase;
    }

    .history-row.is-school .history-detail-table th {
        background: #eff8ff !important;
    }

    .history-row.is-cooperative .history-detail-table th {
        background: #fffaeb !important;
    }

    .history-row.is-income .history-detail-table th {
        background: #f4f3ff !important;
    }

    .history-detail-table td {
        min-height: 39px !important;
        padding: 8px 10px !important;
        color: #344054 !important;
        background: #fff !important;
        border-bottom: 1px solid #eef0f3 !important;
        font-size: 9.5px !important;
        line-height: 1.4;
        vertical-align: middle !important;
    }

    .history-detail-table tbody tr:last-child td {
        border-bottom: 1px solid #d0d5dd !important;
    }

    .history-detail-table tfoot td {
        color: #101828 !important;
        font-weight: 700;
    }

    .history-detail-table th:first-child,
    .history-detail-table td:first-child {
        width: auto;
    }

    .history-detail-table th:not(:first-child),
    .history-detail-table td:not(:first-child) {
        width: 125px;
    }

    .history-money {
        font-variant-numeric: tabular-nums;
        text-align: right !important;
        white-space: nowrap;
    }

    .history-period-label,
    .history-period-value {
        display: block;
    }

    .history-period-label {
        margin-bottom: 2px;
        color: #98a2b3;
        font-size: 7.5px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .history-period-value {
        color: #344054;
        font-size: 9.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .history-empty {
        display: grid;
        min-height: 250px;
        place-items: center;
        padding: 32px 18px;
        text-align: center;
    }

    .history-empty i {
        color: #98a2b3;
        font-size: 22px;
    }

    .history-empty strong,
    .history-empty span {
        display: block;
    }

    .history-empty strong {
        margin-top: 11px;
        color: #344054;
        font-size: 12px;
    }

    .history-empty span {
        margin-top: 4px;
        color: #667085;
        font-size: 10px;
    }

    .history-list-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 13px 18px;
        color: #667085;
        background: #fff;
        border-top: 1px solid #e4e7ec;
        font-size: 9.5px;
    }

    .history-pagination {
        justify-content: flex-end;
        gap: 7px;
    }

    .history-pagination a,
    .history-pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 10px;
        color: #475467;
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 6px;
        font-size: 9px;
        font-weight: 600;
        text-decoration: none;
    }

    .history-pagination span {
        border: 0;
    }

    @media (max-width: 1100px) {
        .history-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }

        .history-list-head,
        .history-row-summary {
            grid-template-columns:
                minmax(190px, 1.35fr)
                minmax(145px, 1fr)
                minmax(105px, .72fr)
                minmax(95px, .62fr)
                minmax(115px, .72fr)
                32px;
            gap: 10px;
        }
    }

    @media (max-width: 820px) {
        .history-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .history-heading-actions,
        .history-heading-actions .history-button {
            width: 100%;
        }

        .history-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .history-filter-footer,
        .history-summary,
        .history-list-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .history-filter-context {
            white-space: normal;
        }

        .history-filter-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .history-filter-actions .history-button:only-child {
            grid-column: 1 / -1;
        }

        .history-list-head {
            display: none;
        }

        .history-row-summary {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 11px 14px;
            min-height: 0;
            padding: 15px;
        }

        .history-student {
            grid-column: 1;
        }

        .history-toggle {
            grid-column: 2;
            grid-row: 1;
        }

        .history-column,
        .history-source,
        .history-total {
            display: grid;
            grid-column: 1 / -1;
            grid-template-columns: 94px minmax(0, 1fr);
            align-items: start;
            gap: 9px;
            text-align: left;
        }

        .history-column::before,
        .history-source::before,
        .history-total::before {
            color: #98a2b3;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            content: attr(data-label);
        }

        .history-total {
            font-size: 12px;
        }

        .history-detail {
            padding: 14px 15px 16px;
        }

        .history-detail-heading {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 520px) {
        .history-panel-heading,
        .history-filter-form,
        .history-summary {
            padding-right: 14px;
            padding-left: 14px;
        }

        .history-filter-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .history-filter-actions {
            grid-template-columns: minmax(0, 1fr);
        }

        .history-filter-actions .history-button:only-child {
            grid-column: auto;
        }

        .history-detail-actions,
        .history-detail-actions .history-button {
            width: 100%;
        }

        .history-pagination {
            justify-content: space-between;
            width: 100%;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="history-page">
            @if(session('success'))
                <div class="history-alert">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="history-alert" style="color:#b42318;background:#fef3f2;border-color:#fecdca">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @php
                $historyHeading = match($selectedJenisPembayaran) {
                    'sekolah' => ['Riwayat pembayaran siswa', 'Seluruh pembayaran tagihan siswa yang telah diproses.'],
                    'koperasi' => ['Riwayat koperasi', 'Seluruh transaksi penjualan koperasi yang telah diproses.'],
                    'pemasukan' => ['Riwayat pemasukan', 'Seluruh transaksi pemasukan lainnya yang telah dicatat.'],
                    default => ['Riwayat transaksi', 'Tagihan siswa, transaksi koperasi, dan pemasukan lainnya tersaji dalam satu daftar.'],
                };
            @endphp

            <header class="history-heading">
                <div>
                    <p class="history-eyebrow">Pembayaran</p>
                    <h1 class="history-title" data-page-title>{{ $historyHeading[0] }}</h1>
                    <p class="history-subtitle">{{ $historyHeading[1] }}</p>
                </div>

                <div class="history-heading-actions">
                    <a class="history-button" href="{{ route('tagihan.index.grouped') }}">
                        <i class="fas fa-file-invoice" aria-hidden="true"></i>
                        <span>Kembali ke tagihan</span>
                    </a>
                </div>
            </header>

            @if(in_array($selectedJenisPembayaran, ['sekolah', 'koperasi', 'pemasukan'], true))
                @include('partials.transaction-tabs', ['module' => $selectedJenisPembayaran, 'active' => 'history'])
            @endif

            <section class="history-panel">
                <div class="history-panel-heading">
                    <div>
                        <h2 class="history-panel-title">Daftar transaksi</h2>
                        <p class="history-panel-context">Semua sekolah dan kelas ditampilkan secara default.</p>
                    </div>
                </div>

                <form class="history-filter-form" id="historyFilterForm" method="GET" action="{{ route('riwayat.index') }}">
                    <div class="history-filter-grid">
                        <div class="history-field">
                            <label for="historySearch">Cari transaksi</label>
                            <input
                                id="historySearch"
                                name="search"
                                type="search"
                                value="{{ $search }}"
                                placeholder="Nama, NIS, atau nomor transaksi..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="history-field">
                            <label for="historyType">Jenis transaksi</label>
                            <select id="historyType" name="jenis_pembayaran">
                                <option value="">Semua transaksi</option>
                                <option value="sekolah" {{ $selectedJenisPembayaran === 'sekolah' ? 'selected' : '' }}>Tagihan siswa</option>
                                <option value="koperasi" {{ $selectedJenisPembayaran === 'koperasi' ? 'selected' : '' }}>Koperasi</option>
                                <option value="pemasukan" {{ $selectedJenisPembayaran === 'pemasukan' ? 'selected' : '' }}>Pemasukan lainnya</option>
                            </select>
                        </div>

                        <div class="history-field">
                            <label for="historySchool">Sekolah</label>
                            <select id="historySchool" name="sekolah_id">
                                <option value="">Semua sekolah</option>
                                @foreach($sekolahList as $sekolah)
                                    <option value="{{ $sekolah->id }}" {{ $selectedSekolah === $sekolah->id ? 'selected' : '' }}>
                                        {{ $sekolah->nama_sekolah }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="history-field">
                            <label for="historyClass">Kelas</label>
                            <select id="historyClass"
                                    name="kelas_id"
                                    data-class-filter-for="historySchool"
                                    data-all-label="Semua kelas">
                                <option value="">Semua kelas</option>
                                @foreach($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}"
                                            data-school-id="{{ $kelas->sekolah_id }}"
                                            data-school-name="{{ $kelas->sekolah?->nama_sekolah }}"
                                            data-class-label="{{ $classLabel($kelas) }}"
                                            {{ $selectedKelas === $kelas->id ? 'selected' : '' }}>
                                        {{ $classLabel($kelas) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="history-field">
                            <label for="historyStartDate">Dari tanggal</label>
                            <input id="historyStartDate" name="start_date" type="date" value="{{ $startDate }}">
                        </div>

                        <div class="history-field">
                            <label for="historyEndDate">Sampai tanggal</label>
                            <input id="historyEndDate" name="end_date" type="date" value="{{ $endDate }}" min="{{ $startDate }}">
                        </div>

                        <a class="history-button history-reset-button" href="{{ route('riwayat.index') }}" data-filter-reset>
                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                            <span>Reset</span>
                        </a>

                    </div>
                </form>

                <div class="history-summary">
                    <div class="history-summary-group">
                        <span><strong>{{ number_format($transactionSummary['total'], 0, ',', '.') }}</strong> transaksi</span>
                        <span class="history-summary-type is-school">{{ number_format($transactionSummary['sekolah'], 0, ',', '.') }} tagihan siswa</span>
                        <span class="history-summary-type is-cooperative">{{ number_format($transactionSummary['koperasi'], 0, ',', '.') }} koperasi</span>
                        <span class="history-summary-type is-income">{{ number_format($transactionSummary['pemasukan'], 0, ',', '.') }} pemasukan lainnya</span>
                    </div>
                    <span>Total diterima <strong>{{ $formatRupiah($transactionSummary['nominal']) }}</strong></span>
                </div>

                @if($transaksi->isNotEmpty())
                    <div class="history-list-head" aria-hidden="true">
                        <span>Sumber / transaksi</span>
                        <span>Sekolah / kelas</span>
                        <span>Tanggal</span>
                        <span>Jenis</span>
                        <span style="text-align:right">Total</span>
                        <span></span>
                    </div>

                    <div class="history-list">
                        @foreach($transaksi as $tx)
                            @php
                                $siswa = $tx['siswa'];
                                $items = collect($tx['items']);
                                $firstItem = $items->first();
                                $sourceType = $tx['source_type'] ?? 'sekolah';
                                $isSchool = $sourceType === 'sekolah';
                                $isCooperative = $sourceType === 'koperasi';
                                $isIncome = $sourceType === 'pemasukan';
                                $school = $tx['sekolah'] ?? $siswa?->sekolah;
                                $class = $tx['kelas'] ?? $siswa?->kelas;
                                $reference = $tx['transaction_id']
                                    ?: ($firstItem?->nomor_kwitansi ?: 'Transaksi #' . ($firstItem?->id ?? $loop->iteration));
                                $incomeSource = trim((string) ($firstItem?->sumber ?? ''));
                                $recordTitle = $isIncome
                                    ? ($incomeSource !== '' ? $incomeSource : 'Pemasukan lainnya')
                                    : ($siswa?->nama ?? 'Siswa tidak tersedia');
                                $initial = mb_strtoupper(mb_substr(trim($recordTitle), 0, 1));
                                $receiptUrl = match($sourceType) {
                                    'sekolah' => route('pembayaran.kwitansi.grup', ['ids' => $tx['ids']]),
                                    'koperasi' => route('koperasi.penjualan.kwitansi', $tx['ids']),
                                    default => null,
                                };
                                $correctionUrl = $isSchool && $firstItem
                                    ? route('pembayaran.koreksi.edit', $firstItem->id)
                                    : null;
                                $receiptPdfUrl = $isSchool
                                    ? route('pembayaran.kwitansi.grup.pdf', ['ids' => $tx['ids']])
                                    : null;
                                $sourceTone = match($sourceType) {
                                    'koperasi' => 'is-cooperative',
                                    'pemasukan' => 'is-income',
                                    default => 'is-school',
                                };
                                $transactionValue = (float) $tx['total_bayar'] + (float) $tx['total_diskon'];
                            @endphp

                            <details class="history-row {{ $sourceTone }}">
                                <summary class="history-row-summary">
                                    <div class="history-student">
                                        <span class="history-avatar">{{ $initial ?: '?' }}</span>
                                        <span class="history-student-copy">
                                            <strong>{{ $recordTitle }}</strong>
                                            <span>{{ $isIncome ? 'Pemasukan non-tagihan' : 'NIS ' . ($siswa?->nis ?? '-') }}</span>
                                            <span>{{ $reference }}</span>
                                        </span>
                                    </div>

                                    <div class="history-column" data-label="Sekolah / kelas">
                                        <span class="history-column-copy">
                                            <strong>{{ $school?->nama_sekolah ?? 'Sekolah belum diatur' }}</strong>
                                            <span>{{ $isIncome ? 'Tidak terkait kelas' : $classLabel($class) }}</span>
                                        </span>
                                    </div>

                                    <div class="history-column" data-label="Tanggal">
                                        <span class="history-column-copy">
                                            <strong>{{ $isIncome ? 'Diterima' : 'Bayar' }} {{ \Carbon\Carbon::parse($tx['tanggal_bayar'])->translatedFormat('d M Y') }}</strong>
                                            <span>{{ $isIncome ? 'Dicatat sebagai pemasukan langsung' : 'Kuitansi ' . \Carbon\Carbon::parse($tx['tanggal_kwitansi'] ?? $tx['tanggal_bayar'])->translatedFormat('d M Y') . ' · ' . ($tx['metode_bayar'] ?: 'Metode tidak dicatat') }}</span>
                                        </span>
                                    </div>

                                    <div class="history-source" data-label="Jenis">
                                        <span class="history-source-badge {{ $sourceTone }}">
                                            {{ $tx['source_label'] }}
                                        </span>
                                    </div>

                                    <div class="history-total" data-label="Total">
                                        {{ $formatRupiah($tx['total_bayar']) }}
                                    </div>

                                    <span class="history-toggle" aria-hidden="true">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </summary>

                                <div class="history-detail">
                                    <div class="history-detail-heading">
                                        <div>
                                            <h3 class="history-detail-title">{{ $isIncome ? 'Rincian pemasukan' : ($isCooperative ? 'Rincian barang' : 'Rincian tagihan') }}</h3>
                                            <span class="history-detail-caption">{{ $items->count() }} item dalam transaksi {{ $reference }}</span>
                                        </div>
                                        <div class="history-detail-actions">
                                            @if($canCorrectPayment && $correctionUrl)
                                                <a class="history-button is-compact is-warning" href="{{ $correctionUrl }}">
                                                    <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                                    <span>Koreksi transaksi</span>
                                                </a>
                                            @endif
                                            @if($receiptUrl)
                                                <a class="history-button is-compact" href="{{ $receiptUrl }}" target="_blank" rel="noopener">
                                                    <i class="fas fa-print" aria-hidden="true"></i>
                                                    <span>Cetak kwitansi</span>
                                                </a>
                                            @elseif($isIncome && $canManageIncome)
                                                <a class="history-button is-compact" href="{{ route('pemasukan.show', $tx['ids']) }}">
                                                    <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                                    <span>Lihat data pemasukan</span>
                                                </a>
                                            @endif
                                            @if($receiptPdfUrl)
                                                <a class="history-button is-compact is-pdf" href="{{ $receiptPdfUrl }}" target="_blank" rel="noopener">
                                                    <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                                    <span>Download PDF</span>
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="history-detail-table-wrap">
                                        <table class="history-detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Rincian</th>
                                                    <th>{{ $isIncome ? 'Keterangan' : ($isCooperative ? 'Jumlah' : 'Periode') }}</th>
                                                    <th class="history-money">{{ $isIncome ? 'Nilai pemasukan' : 'Nilai transaksi' }}</th>
                                                    <th class="history-money">Potongan</th>
                                                    <th class="history-money">Diterima</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $item)
                                                    @if($isSchool)
                                                        @php
                                                            $historyBillPeriod = trim((string) ($item->tagihan?->periode ?? $item->periode ?? ''));
                                                            $historyIsMonthlyBill = $item->tagihan?->tipe === 'bulanan';
                                                            $historyPeriodLabel = $historyBillPeriod !== '' ? $historyBillPeriod : '-';

                                                            if ($historyIsMonthlyBill && preg_match('/^\d{4}-\d{2}$/', $historyBillPeriod)) {
                                                                $historyPeriodLabel = \Carbon\Carbon::createFromFormat('Y-m-d', $historyBillPeriod . '-01')
                                                                    ->locale('id')
                                                                    ->translatedFormat('F Y');
                                                            }
                                                        @endphp
                                                    @endif
                                                    <tr>
                                                        @if($isIncome)
                                                            <td><strong>{{ $item->sumber ?: 'Pemasukan lainnya' }}</strong></td>
                                                            <td>{{ $item->keterangan ?: 'Tidak ada keterangan' }}</td>
                                                            <td class="history-money">{{ $formatRupiah($item->jumlah) }}</td>
                                                            <td class="history-money">—</td>
                                                            <td class="history-money"><strong>{{ $formatRupiah($item->jumlah) }}</strong></td>
                                                        @elseif($isCooperative)
                                                            <td><strong>{{ $item->nama_barang }}</strong></td>
                                                            <td>{{ number_format($item->jumlah, 0, ',', '.') }} item</td>
                                                            <td class="history-money">{{ $formatRupiah($item->harga_satuan * $item->jumlah) }}</td>
                                                            <td class="history-money">—</td>
                                                            <td class="history-money"><strong>{{ $formatRupiah($item->subtotal) }}</strong></td>
                                                        @else
                                                            <td><strong>{{ $item->tagihan?->nama_tagihan ?? 'Tagihan dihapus' }}</strong></td>
                                                            <td>
                                                                <span class="history-period-label">{{ $historyIsMonthlyBill ? 'Bulan pembayaran' : 'Periode' }}</span>
                                                                <strong class="history-period-value">{{ $historyPeriodLabel }}</strong>
                                                            </td>
                                                            <td class="history-money">{{ $formatRupiah($item->jumlah_bayar + $item->diskon) }}</td>
                                                            <td class="history-money">{{ $item->diskon > 0 ? '- ' . $formatRupiah($item->diskon) : '—' }}</td>
                                                            <td class="history-money"><strong>{{ $formatRupiah($item->jumlah_bayar) }}</strong></td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2">Total transaksi</td>
                                                    <td class="history-money">{{ $formatRupiah($transactionValue) }}</td>
                                                    <td class="history-money">{{ $tx['total_diskon'] > 0 ? '- ' . $formatRupiah($tx['total_diskon']) : '—' }}</td>
                                                    <td class="history-money">{{ $formatRupiah($tx['total_bayar']) }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>

                    <footer class="history-list-footer">
                        <span>
                            Menampilkan {{ number_format($transaksi->firstItem(), 0, ',', '.') }}–{{ number_format($transaksi->lastItem(), 0, ',', '.') }}
                            dari {{ number_format($transaksi->total(), 0, ',', '.') }} transaksi
                        </span>

                        @if($transaksi->hasPages())
                            <nav class="history-pagination" aria-label="Navigasi halaman transaksi">
                                @if($transaksi->onFirstPage())
                                    <span>Sebelumnya</span>
                                @else
                                    <a href="{{ $transaksi->previousPageUrl() }}">Sebelumnya</a>
                                @endif
                                <span>Halaman {{ $transaksi->currentPage() }} dari {{ $transaksi->lastPage() }}</span>
                                @if($transaksi->hasMorePages())
                                    <a href="{{ $transaksi->nextPageUrl() }}">Berikutnya</a>
                                @else
                                    <span>Berikutnya</span>
                                @endif
                            </nav>
                        @endif
                    </footer>
                @else
                    <div class="history-empty">
                        <div>
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <strong>Transaksi tidak ditemukan</strong>
                            <span>Ubah filter atau kata pencarian untuk menampilkan transaksi lain.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('historyFilterForm');
    const search = document.getElementById('historySearch');
    const type = document.getElementById('historyType');
    const school = document.getElementById('historySchool');
    const classSelect = document.getElementById('historyClass');
    const startDate = document.getElementById('historyStartDate');
    const endDate = document.getElementById('historyEndDate');
    const rows = [...document.querySelectorAll('.history-row')];

    school?.addEventListener('change', () => {
        if (classSelect) {
            classSelect.value = '';
            classSelect.disabled = true;
        }
        form?.submit();
    });

    classSelect?.addEventListener('change', () => form?.submit());
    type?.addEventListener('change', () => form?.submit());

    let searchTimer;
    search?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => form?.submit(), 500);
    });

    startDate?.addEventListener('change', () => {
        if (!endDate) return;
        endDate.min = startDate.value;
        if (endDate.value && endDate.value < startDate.value) {
            endDate.value = startDate.value;
        }
        form?.submit();
    });

    endDate?.addEventListener('change', () => form?.submit());

    rows.forEach((row) => {
        row.addEventListener('toggle', () => {
            if (!row.open) return;
            rows.forEach((otherRow) => {
                if (otherRow !== row) otherRow.open = false;
            });
        });
    });
});
</script>
@endsection
