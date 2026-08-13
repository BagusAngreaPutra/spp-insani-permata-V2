@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Riwayat Transaksi')

@section('content')
@php
    $adminUser = Auth::guard('web')->user();
    $canCorrectPayment = $adminUser?->hasPermission('pembayaran.process') ?? false;
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
        'sekolah' => 'Pembayaran sekolah',
        'koperasi' => 'Koperasi',
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
        padding: 16px 18px;
        background: #fbfcfd;
        border-bottom: 1px solid #e4e7ec;
    }

    .history-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .history-field {
        min-width: 0;
    }

    .history-field.is-wide {
        grid-column: span 3;
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
        justify-content: space-between;
        gap: 14px;
        margin-top: 14px;
        padding-top: 13px;
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
        color: #1d6b4c;
        background: #eff8ff;
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
        .history-filter-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

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

        .history-field.is-wide {
            grid-column: 1 / -1;
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

        .history-field.is-wide {
            grid-column: auto;
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

            <header class="history-heading">
                <div>
                    <p class="history-eyebrow">Pembayaran</p>
                    <h1 class="history-title" data-page-title>Riwayat transaksi</h1>
                    <p class="history-subtitle">Filter transaksi, buka rincian tagihan, lalu cetak kwitansi dari satu daftar.</p>
                </div>

                <div class="history-heading-actions">
                    <a class="history-button" href="{{ route('tagihan.index.grouped') }}">
                        <i class="fas fa-file-invoice" aria-hidden="true"></i>
                        <span>Kembali ke tagihan</span>
                    </a>
                </div>
            </header>

            <section class="history-panel">
                <div class="history-panel-heading">
                    <div>
                        <h2 class="history-panel-title">Daftar transaksi</h2>
                        <p class="history-panel-context">Semua sekolah dan kelas ditampilkan secara default.</p>
                    </div>
                </div>

                <form class="history-filter-form" id="historyFilterForm" method="GET" action="{{ route('riwayat.index') }}">
                    <div class="history-filter-grid">
                        <div class="history-field is-wide">
                            <label for="historySearch">Cari transaksi</label>
                            <input
                                id="historySearch"
                                name="search"
                                type="search"
                                value="{{ $search }}"
                                placeholder="Nama, NIS, nomor transaksi, tagihan, atau barang..."
                                autocomplete="off"
                            >
                        </div>

                        <div class="history-field">
                            <label for="historyType">Jenis transaksi</label>
                            <select id="historyType" name="jenis_pembayaran">
                                <option value="">Semua transaksi</option>
                                <option value="sekolah" {{ $selectedJenisPembayaran === 'sekolah' ? 'selected' : '' }}>Pembayaran sekolah</option>
                                <option value="koperasi" {{ $selectedJenisPembayaran === 'koperasi' ? 'selected' : '' }}>Koperasi</option>
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
                    </div>

                    <div class="history-filter-footer">
                        <p class="history-filter-context">
                            {{ $filterContext ?: 'Menampilkan transaksi dari semua sekolah, kelas, dan jenis pembayaran.' }}
                        </p>
                        <div class="history-filter-actions">
                            @if($hasActiveFilters)
                                <a class="history-button" href="{{ route('riwayat.index') }}">
                                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                    <span>Reset</span>
                                </a>
                            @endif
                            <button class="history-button is-primary" type="submit">
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                <span>Tampilkan</span>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="history-summary">
                    <div class="history-summary-group">
                        <span><strong>{{ number_format($transactionSummary['total'], 0, ',', '.') }}</strong> transaksi</span>
                        <span>{{ number_format($transactionSummary['sekolah'], 0, ',', '.') }} pembayaran sekolah</span>
                        <span>{{ number_format($transactionSummary['koperasi'], 0, ',', '.') }} koperasi</span>
                    </div>
                    <span>Total diterima <strong>{{ $formatRupiah($transactionSummary['nominal']) }}</strong></span>
                </div>

                @if($transaksi->isNotEmpty())
                    <div class="history-list-head" aria-hidden="true">
                        <span>Siswa / transaksi</span>
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
                                $isCooperative = ($tx['source_type'] ?? 'sekolah') === 'koperasi';
                                $reference = $tx['transaction_id']
                                    ?: ($firstItem?->nomor_kwitansi ?: 'Transaksi #' . ($firstItem?->id ?? $loop->iteration));
                                $studentName = $siswa?->nama ?? 'Siswa tidak tersedia';
                                $initial = mb_strtoupper(mb_substr(trim($studentName), 0, 1));
                                $receiptUrl = $isCooperative
                                    ? route('koperasi.penjualan.kwitansi', $tx['ids'])
                                    : route('pembayaran.kwitansi.grup', ['ids' => $tx['ids']]);
                                $correctionUrl = !$isCooperative && $firstItem
                                    ? route('pembayaran.koreksi.edit', $firstItem->id)
                                    : null;
                                $receiptPdfUrl = !$isCooperative
                                    ? route('pembayaran.kwitansi.grup', ['ids' => $tx['ids'], 'download' => 1])
                                    : null;
                                $transactionValue = (float) $tx['total_bayar'] + (float) $tx['total_diskon'];
                            @endphp

                            <details class="history-row">
                                <summary class="history-row-summary">
                                    <div class="history-student">
                                        <span class="history-avatar">{{ $initial ?: '?' }}</span>
                                        <span class="history-student-copy">
                                            <strong>{{ $studentName }}</strong>
                                            <span>NIS {{ $siswa?->nis ?? '-' }}</span>
                                            <span>{{ $reference }}</span>
                                        </span>
                                    </div>

                                    <div class="history-column" data-label="Sekolah / kelas">
                                        <span class="history-column-copy">
                                            <strong>{{ $siswa?->sekolah?->nama_sekolah ?? 'Sekolah belum diatur' }}</strong>
                                            <span>{{ $classLabel($siswa?->kelas) }}</span>
                                        </span>
                                    </div>

                                    <div class="history-column" data-label="Tanggal">
                                        <span class="history-column-copy">
                                            <strong>Bayar {{ \Carbon\Carbon::parse($tx['tanggal_bayar'])->translatedFormat('d M Y') }}</strong>
                                            <span>Kuitansi {{ \Carbon\Carbon::parse($tx['tanggal_kwitansi'] ?? $tx['tanggal_bayar'])->translatedFormat('d M Y') }} · {{ $tx['metode_bayar'] ?: 'Metode tidak dicatat' }}</span>
                                        </span>
                                    </div>

                                    <div class="history-source" data-label="Jenis">
                                        <span class="history-source-badge {{ $isCooperative ? '' : 'is-school' }}">
                                            {{ $isCooperative ? 'Koperasi' : 'Sekolah' }}
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
                                            <h3 class="history-detail-title">{{ $isCooperative ? 'Rincian barang' : 'Rincian tagihan' }}</h3>
                                            <span class="history-detail-caption">{{ $items->count() }} item dalam transaksi {{ $reference }}</span>
                                        </div>
                                        <div class="history-detail-actions">
                                            @if($canCorrectPayment && $correctionUrl)
                                                <a class="history-button is-compact is-warning" href="{{ $correctionUrl }}">
                                                    <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                                    <span>Koreksi transaksi</span>
                                                </a>
                                            @endif
                                            <a class="history-button is-compact" href="{{ $receiptUrl }}" target="_blank" rel="noopener">
                                                <i class="fas fa-print" aria-hidden="true"></i>
                                                <span>Cetak kwitansi</span>
                                            </a>
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
                                                    <th>{{ $isCooperative ? 'Jumlah' : 'Periode' }}</th>
                                                    <th class="history-money">Nilai transaksi</th>
                                                    <th class="history-money">Potongan</th>
                                                    <th class="history-money">Diterima</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $item)
                                                    @if(!$isCooperative)
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
                                                        @if($isCooperative)
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

    startDate?.addEventListener('change', () => {
        if (!endDate) return;
        endDate.min = startDate.value;
        if (endDate.value && endDate.value < startDate.value) {
            endDate.value = startDate.value;
        }
    });

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
