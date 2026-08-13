@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Tagihan Siswa')

@section('content')
@php
    $adminUser = Auth::guard('web')->user();
    $canGenerate = $adminUser?->hasPermission('tagihan.manage') ?? false;
    $canPay = $adminUser?->hasPermission('pembayaran.process') ?? false;
    $canViewHistory = $adminUser?->hasPermission('riwayat.view') ?? false;
    $formatRupiah = static fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $classLabel = static function ($kelas) {
        if (!$kelas) {
            return 'Semua kelas';
        }

        $name = trim((string) $kelas->nama_kelas);

        return in_array($name, ['', '-', '–'], true)
            ? $kelas->label_tingkat
            : $kelas->label_tingkat . ' · ' . $name;
    };
    $selectedContext = match (true) {
        (bool) $selectedKelas => ($selectedSekolah?->nama_sekolah
            ?? $selectedKelas->sekolah?->nama_sekolah
            ?? 'Sekolah belum diatur') . ' · ' . $classLabel($selectedKelas),
        (bool) $selectedSekolah => $selectedSekolah->nama_sekolah . ' · Semua kelas',
        default => 'Semua sekolah · Semua kelas',
    };
    $selectedContext .= ' · ' . ($selectedTahunAjaran?->label ?? 'Semua tahun ajaran');
@endphp

@push('page-styles')
<style>
    .billing-page {
        display: grid;
        gap: 18px;
    }

    .billing-heading,
    .billing-heading-actions,
    .billing-panel-heading,
    .billing-table-tools,
    .billing-search,
    .billing-status-filters,
    .billing-student,
    .billing-row-actions,
    .billing-panel-footer {
        display: flex;
        align-items: center;
    }

    .billing-heading {
        justify-content: space-between;
        gap: 24px;
    }

    .billing-eyebrow {
        margin: 0 0 3px;
        color: #98a2b3;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .billing-title {
        margin: 0;
        color: #101828;
        font-size: clamp(25px, 2.6vw, 32px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.16;
    }

    .billing-subtitle {
        margin: 7px 0 0;
        color: #667085;
        font-size: 12px;
    }

    .billing-heading-actions {
        justify-content: flex-end;
        gap: 8px;
    }

    .billing-button,
    .billing-row-action,
    .billing-status-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        margin: 0 !important;
        color: #344054 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        font-family: inherit !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }

    .billing-button {
        min-height: 38px !important;
        padding: 0 13px !important;
        font-size: 11px !important;
    }

    .billing-button.is-primary,
    .billing-row-action.is-primary {
        color: #fff !important;
        background: #1d6b4c !important;
        border-color: #1d6b4c !important;
    }

    .billing-button:hover,
    .billing-row-action:hover {
        color: #101828 !important;
        background: #f9fafb !important;
    }

    .billing-button.is-primary:hover,
    .billing-row-action.is-primary:hover {
        color: #fff !important;
        background: #15533b !important;
        border-color: #15533b !important;
    }

    .billing-alert {
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
        padding: 11px 13px;
        color: #087451;
        background: #ecfdf3;
        border: 1px solid #abefc6;
        border-radius: 8px;
        font-size: 11px;
    }

    .billing-alert.is-error {
        color: #b42318;
        background: #fef3f2;
        border-color: #fecdca;
    }

    .billing-alert > i {
        display: grid;
        place-items: center;
        width: 30px;
        height: 30px;
        background: rgba(255, 255, 255, .7);
        border-radius: 7px;
    }

    .billing-alert strong,
    .billing-alert span {
        display: block;
    }

    .billing-alert strong {
        color: inherit;
        font-size: 11px;
    }

    .billing-alert span {
        margin-top: 1px;
        color: #667085;
        font-size: 10px;
    }

    .billing-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .billing-stat {
        min-width: 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .billing-stat-main {
        min-height: 106px;
        padding: 17px 18px 13px;
    }

    .billing-stat-icon {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        margin-bottom: 15px;
        color: #1d6b4c;
        background: #f1f9f5;
        border: 1px solid #dbe8fc;
        border-radius: 8px;
        font-size: 12px;
    }

    .billing-stat.is-green .billing-stat-icon {
        color: #079455;
        background: #ecfdf3;
        border-color: #d1fadf;
    }

    .billing-stat.is-amber .billing-stat-icon {
        color: #dc6803;
        background: #fffaeb;
        border-color: #fef0c7;
    }

    .billing-stat.is-red .billing-stat-icon {
        color: #d92d20;
        background: #fef3f2;
        border-color: #fee4e2;
    }

    .billing-stat-label,
    .billing-stat-value,
    .billing-stat-note {
        display: block;
    }

    .billing-stat-label {
        color: #667085;
        font-size: 10px;
    }

    .billing-stat-value {
        margin-top: 3px;
        overflow: hidden;
        color: #101828;
        font-size: clamp(20px, 2vw, 27px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .billing-stat-note {
        min-height: 36px;
        padding: 10px 18px;
        overflow: hidden;
        color: #667085;
        background: #fafbfc;
        border-top: 1px solid #e4e7ec;
        font-size: 9.5px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .billing-panel {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .billing-panel-heading {
        justify-content: space-between;
        gap: 18px;
        min-height: 58px;
        padding: 13px 18px;
        border-bottom: 1px solid #e4e7ec;
    }

    .billing-panel-title {
        margin: 0;
        color: #101828;
        font-size: 13px;
        font-weight: 650;
    }

    .billing-panel-context {
        margin: 3px 0 0;
        color: #667085;
        font-size: 10px;
    }

    .billing-scope-form {
        display: grid;
        grid-template-columns: repeat(3, minmax(160px, 1fr)) auto;
        align-items: end;
        gap: 10px;
        padding: 15px 18px;
        background: #fafbfc;
        border-bottom: 1px solid #e4e7ec;
    }

    .billing-field label {
        margin-bottom: 5px !important;
        color: #475467 !important;
        font-size: 9.5px !important;
    }

    .billing-field select,
    .billing-search input {
        width: 100% !important;
        height: 36px !important;
        margin: 0 !important;
        color: #344054 !important;
        background-color: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        font-family: inherit !important;
        font-size: 11px !important;
    }

    .billing-field select {
        padding: 0 32px 0 10px !important;
    }

    .billing-table-tools {
        justify-content: space-between;
        gap: 16px;
        padding: 12px 18px;
        border-bottom: 1px solid #e4e7ec;
    }

    .billing-search {
        position: relative;
        flex: 1 1 280px;
        max-width: 380px;
    }

    .billing-search > i {
        position: absolute;
        left: 11px;
        z-index: 1;
        color: #98a2b3;
        font-size: 10px;
        pointer-events: none;
    }

    .billing-search input {
        padding: 0 34px 0 31px !important;
    }

    .billing-search-clear {
        position: absolute !important;
        right: 5px !important;
        display: none !important;
        place-items: center !important;
        width: 26px !important;
        height: 26px !important;
        min-height: 0 !important;
        padding: 0 !important;
        color: #667085 !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 5px !important;
        font-size: 10px !important;
    }

    .billing-search-clear.is-visible {
        display: grid !important;
    }

    .billing-status-filters {
        flex: 0 0 auto;
        gap: 4px;
        padding: 3px;
        background: #f2f4f7;
        border-radius: 7px;
    }

    .billing-status-button {
        min-height: 28px !important;
        padding: 0 9px !important;
        color: #667085 !important;
        background: transparent !important;
        border: 0 !important;
        font-size: 9.5px !important;
    }

    .billing-status-button.is-active {
        color: #101828 !important;
        background: #fff !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .08) !important;
    }

    .billing-table-wrap {
        max-width: 100%;
        overflow-x: auto;
    }

    .billing-table {
        width: 100% !important;
        min-width: 0 !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
    }

    .billing-table .billing-col-student { width: 27%; }
    .billing-table .billing-col-status { width: 15%; }
    .billing-table .billing-col-count { width: 8%; }
    .billing-table .billing-col-total { width: 14%; }
    .billing-table .billing-col-paid { width: 12%; }
    .billing-table .billing-col-remaining { width: 13%; }
    .billing-table .billing-col-action { width: 11%; }

    body:has(.app-sidebar) .main-content .billing-page .billing-table.pi-responsive-table {
        width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed !important;
    }

    .billing-table th {
        height: 37px !important;
        padding: 8px 12px !important;
        color: #667085 !important;
        background: #f9fafb !important;
        border-bottom: 1px solid #e4e7ec !important;
        font-size: 9px !important;
        font-weight: 600 !important;
    }

    .billing-table td {
        height: 59px !important;
        padding: 9px 12px !important;
        color: #344054 !important;
        background: #fff !important;
        border-bottom: 1px solid #eef0f3 !important;
        font-size: 10.5px !important;
    }

    .billing-table tbody tr:hover td {
        background: #fbfcfe !important;
    }

    .billing-student {
        width: 100%;
        max-width: 100%;
        gap: 9px;
        min-width: 0;
        overflow: hidden;
    }

    .billing-student-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 31px;
        width: 31px;
        height: 31px;
        color: #1d6b4c;
        background: #f1f9f5;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 700;
    }

    .billing-student-copy {
        flex: 1 1 auto;
        width: calc(100% - 40px);
        max-width: calc(100% - 40px);
        min-width: 0;
        overflow: hidden;
    }

    .billing-student-copy strong,
    .billing-student-copy span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .billing-student-copy strong {
        max-width: 100%;
        color: #101828;
        font-size: 10.5px;
        font-weight: 650;
    }

    .billing-student-copy span {
        margin-top: 2px;
        color: #98a2b3;
        font-size: 9px;
    }

    .billing-student-copy .billing-student-scope {
        max-width: 100%;
        color: #667085;
        font-size: 8.5px;
    }

    .billing-table .billing-student-cell {
        min-width: 0 !important;
        overflow: hidden !important;
    }

    .billing-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-height: 23px;
        padding: 0 8px;
        color: #475467;
        background: #f2f4f7;
        border-radius: 999px;
        font-size: 8.5px;
        font-weight: 650;
        white-space: nowrap;
    }

    .billing-status::before {
        width: 5px;
        height: 5px;
        background: #98a2b3;
        border-radius: 50%;
        content: "";
    }

    .billing-status.is-paid {
        color: #087451;
        background: #ecfdf3;
    }

    .billing-status.is-paid::before {
        background: #12b76a;
    }

    .billing-status.is-partial {
        color: #b54708;
        background: #fffaeb;
    }

    .billing-status.is-partial::before {
        background: #f79009;
    }

    .billing-status.is-unpaid {
        color: #b42318;
        background: #fef3f2;
    }

    .billing-status.is-unpaid::before {
        background: #f04438;
    }

    .billing-status.is-upcoming {
        color: #a15c07;
        background: #fff7d6;
        border: 1px solid #f4c84a;
    }

    .billing-status.is-upcoming::before {
        background: #eaaa08;
    }

    .billing-money {
        color: #344054;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .billing-money.is-remaining {
        color: #b42318;
        font-weight: 650;
    }

    .billing-money.is-zero {
        color: #087451;
    }

    .billing-row-actions {
        justify-content: flex-end;
        gap: 6px;
    }

    .billing-row-actions form {
        margin: 0 !important;
    }

    .billing-row-action {
        min-height: 31px !important;
        padding: 0 10px !important;
        font-size: 9.5px !important;
        white-space: nowrap !important;
    }

    .billing-panel-footer {
        justify-content: space-between;
        gap: 12px;
        min-height: 43px;
        padding: 10px 18px;
        color: #667085;
        background: #fafbfc;
        font-size: 9.5px;
    }

    .billing-panel-footer strong {
        color: #344054;
        font-weight: 600;
    }

    .billing-empty {
        display: grid;
        place-items: center;
        min-height: 260px;
        padding: 30px;
        color: #667085;
        text-align: center;
    }

    .billing-empty i {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        margin: 0 auto 11px;
        color: #667085;
        background: #f2f4f7;
        border-radius: 9px;
        font-size: 14px;
    }

    .billing-empty strong,
    .billing-empty span {
        display: block;
    }

    .billing-empty strong {
        color: #344054;
        font-size: 12px;
    }

    .billing-empty span {
        max-width: 360px;
        margin-top: 4px;
        font-size: 10px;
    }

    .billing-modal-copy {
        margin: 0 0 14px;
        color: #667085;
        font-size: 11px;
    }

    .billing-checklist {
        display: grid;
        gap: 7px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .billing-checklist li {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #344054;
        font-size: 10.5px;
    }

    .billing-checklist i {
        color: #12a06a;
        font-size: 10px;
    }

    .billing-modal-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 15px;
        padding: 10px;
        color: #854a0e;
        background: #fffaeb;
        border: 1px solid #fef0c7;
        border-radius: 7px;
        font-size: 10px;
    }

    @media (max-width: 1050px) {
        .billing-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .billing-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .billing-heading-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .billing-table-tools {
            align-items: stretch;
            flex-direction: column;
        }

        .billing-search {
            flex: 0 0 auto;
            width: 100%;
            max-width: none;
        }

        .billing-status-filters {
            align-self: flex-start;
        }
    }

    @media (max-width: 700px) {
        .billing-page {
            gap: 14px;
        }

        .billing-heading-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .billing-button {
            width: 100% !important;
        }

        .billing-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .billing-stat-main {
            min-height: 96px;
            padding: 13px 14px 11px;
        }

        .billing-stat-icon {
            width: 31px;
            height: 31px;
            margin-bottom: 10px;
        }

        .billing-stat-value {
            font-size: 19px;
        }

        .billing-stat-note {
            min-height: 38px;
            padding: 9px 14px;
            white-space: normal;
        }

        .billing-scope-form {
            grid-template-columns: 1fr;
        }

        .billing-panel-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .billing-status-filters {
            width: 100%;
            overflow-x: auto;
        }

        .billing-status-button {
            flex: 1 0 auto;
        }

        .billing-table-wrap {
            padding: 10px;
            overflow: visible;
            background: #f9fafb;
        }

        body:has(.app-sidebar) .billing-page .billing-table {
            display: block !important;
            min-width: 0 !important;
            background: transparent !important;
        }

        .billing-table thead {
            display: none !important;
        }

        .billing-table tbody,
        .billing-table tr,
        .billing-table td {
            display: block !important;
            width: 100% !important;
        }

        .billing-table tr[data-student-row] {
            margin-bottom: 9px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 9px;
        }

        .billing-table tr[data-student-row]:last-child {
            margin-bottom: 0;
        }

        .billing-table td {
            display: grid !important;
            grid-template-columns: 104px minmax(0, 1fr) !important;
            align-items: center !important;
            min-height: 38px !important;
            height: auto !important;
            padding: 8px 11px !important;
            text-align: right !important;
            border-bottom: 1px solid #eef0f3 !important;
        }

        .billing-table td::before {
            color: #98a2b3;
            font-size: 8.5px;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            content: attr(data-label);
        }

        .billing-table td.billing-student-cell {
            display: block !important;
            padding: 12px !important;
            text-align: left !important;
        }

        .billing-table td.billing-student-cell::before,
        .billing-table td.billing-action-cell::before {
            display: none;
        }

        .billing-table td.billing-action-cell {
            display: block !important;
            padding: 10px 11px !important;
            border-bottom: 0 !important;
        }

        .billing-row-actions,
        .billing-row-actions form,
        .billing-row-action {
            width: 100% !important;
        }

        .billing-panel-footer {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 350px) {
        .billing-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="billing-page">
            @if(session('success'))
                <div class="billing-alert" role="status">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <div>
                        <strong>Proses selesai</strong>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="billing-alert is-error" role="alert">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <div>
                        <strong>Proses belum berhasil</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <header class="billing-heading">
                <div>
                    <p class="billing-eyebrow">Pembayaran</p>
                    <h1 class="billing-title" data-page-title>Tagihan siswa</h1>
                    <p class="billing-subtitle">Cari siswa, lihat sisa tagihan, lalu proses pembayaran dari satu tampilan.</p>
                </div>

                <div class="billing-heading-actions">
                    @if($canViewHistory)
                        <a class="billing-button" href="{{ route('riwayat.index') }}">
                            <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                            <span>Riwayat pembayaran</span>
                        </a>
                    @endif
                    @if($canGenerate)
                        <button class="billing-button is-primary" type="button" data-bs-toggle="modal" data-bs-target="#generateModal">
                            <i class="fas fa-file-circle-plus" aria-hidden="true"></i>
                            <span>Buat tagihan</span>
                        </button>
                    @endif
                </div>
            </header>

            <section class="billing-stats" aria-label="Ringkasan tagihan kelas">
                <article class="billing-stat">
                    <div class="billing-stat-main">
                        <span class="billing-stat-icon"><i class="fas fa-user-graduate"></i></span>
                        <span class="billing-stat-label">Siswa ditampilkan</span>
                        <strong class="billing-stat-value">{{ number_format($workspaceSummary['total_siswa'], 0, ',', '.') }}</strong>
                    </div>
                    <span class="billing-stat-note">{{ $selectedContext }}</span>
                </article>

                <article class="billing-stat is-amber">
                    <div class="billing-stat-main">
                        <span class="billing-stat-icon"><i class="fas fa-file-invoice"></i></span>
                        <span class="billing-stat-label">Total tagihan</span>
                        <strong class="billing-stat-value">{{ number_format($workspaceSummary['total_tagihan'], 0, ',', '.') }}</strong>
                    </div>
                    <span class="billing-stat-note">{{ $formatRupiah($workspaceSummary['total_nominal']) }} nilai tagihan</span>
                </article>

                <article class="billing-stat is-green">
                    <div class="billing-stat-main">
                        <span class="billing-stat-icon"><i class="fas fa-circle-check"></i></span>
                        <span class="billing-stat-label">Sudah dibayar</span>
                        <strong class="billing-stat-value">{{ $formatRupiah($workspaceSummary['total_dibayar']) }}</strong>
                    </div>
                    <span class="billing-stat-note">{{ number_format($workspaceSummary['siswa_lunas'], 0, ',', '.') }} siswa lunas</span>
                </article>

                <article class="billing-stat is-red">
                    <div class="billing-stat-main">
                        <span class="billing-stat-icon"><i class="fas fa-hourglass-half"></i></span>
                        <span class="billing-stat-label">Sisa tagihan</span>
                        <strong class="billing-stat-value">{{ $formatRupiah($workspaceSummary['sisa_bayar']) }}</strong>
                    </div>
                    <span class="billing-stat-note">Perlu ditindaklanjuti</span>
                </article>
            </section>

            <section class="billing-panel">
                <div class="billing-panel-heading">
                    <div>
                        <h2 class="billing-panel-title">Daftar siswa</h2>
                        <p class="billing-panel-context">{{ $selectedContext }}</p>
                    </div>
                </div>

                @if($sekolahData->isNotEmpty())
                    <form class="billing-scope-form" id="billingScopeForm" method="GET" action="{{ route('tagihan.index.grouped') }}">
                        <div class="billing-field">
                            <label for="billingSchool">Sekolah</label>
                            <select id="billingSchool" name="sekolah">
                                <option value="" {{ !$selectedSekolah ? 'selected' : '' }}>Semua sekolah</option>
                                @foreach($sekolahData as $sekolah)
                                    <option value="{{ $sekolah->id }}" {{ $selectedSekolah?->id === $sekolah->id ? 'selected' : '' }}>
                                        {{ $sekolah->nama_sekolah }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="billing-field">
                            <label for="billingClass">Kelas</label>
                            <select id="billingClass" name="kelas">
                                <option value="" {{ !$selectedKelas ? 'selected' : '' }}>Semua kelas</option>
                                @foreach($availableClasses as $kelas)
                                    <option value="{{ $kelas->id }}" {{ $selectedKelas?->id === $kelas->id ? 'selected' : '' }}>
                                        @if(!$selectedSekolah)
                                            {{ $kelas->sekolah?->nama_sekolah ?? 'Sekolah belum diatur' }} ·
                                        @endif
                                        {{ $classLabel($kelas) }} · {{ $kelas->siswa_count }} siswa
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="billing-field">
                            <label for="billingAcademicYear">Tahun ajaran</label>
                            <select id="billingAcademicYear" name="tahun_ajaran">
                                @foreach($academicYearData as $tahun)
                                    <option value="{{ $tahun->id }}" {{ $selectedTahunAjaran?->id === $tahun->id ? 'selected' : '' }}>
                                        {{ $tahun->label }}{{ $tahun->aktif ? ' · Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button class="billing-button is-primary" type="submit">
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            <span>Tampilkan</span>
                        </button>
                    </form>
                @endif

                @if($studentRows->isNotEmpty())
                    <div class="billing-table-tools">
                        <label class="billing-search" for="billingStudentSearch">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input id="billingStudentSearch" type="search" placeholder="Cari nama, NIS, sekolah, atau kelas..." autocomplete="off">
                            <button class="billing-search-clear" id="billingSearchClear" type="button" aria-label="Hapus pencarian">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </label>

                        <div class="billing-status-filters" aria-label="Filter status tagihan">
                            <button class="billing-status-button is-active" type="button" data-billing-filter="all">Semua</button>
                            <button class="billing-status-button" type="button" data-billing-filter="open">Belum lunas</button>
                            <button class="billing-status-button" type="button" data-billing-filter="belum_jatuh_tempo">Belum jatuh tempo</button>
                            <button class="billing-status-button" type="button" data-billing-filter="sebagian">Sebagian</button>
                            <button class="billing-status-button" type="button" data-billing-filter="lunas">Lunas</button>
                        </div>
                    </div>

                    <div class="billing-table-wrap">
                        <table class="billing-table">
                            <colgroup>
                                <col class="billing-col-student">
                                <col class="billing-col-status">
                                <col class="billing-col-count">
                                <col class="billing-col-total">
                                <col class="billing-col-paid">
                                <col class="billing-col-remaining">
                                <col class="billing-col-action">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Siswa</th>
                                    <th>Status</th>
                                    <th>Tagihan</th>
                                    <th>Total nominal</th>
                                    <th>Dibayar</th>
                                    <th>Sisa</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($studentRows as $student)
                                    @php
                                        $statusLabel = match($student['status']) {
                                             'lunas' => 'Lunas',
                                             'sebagian' => 'Sebagian',
                                             'belum_jatuh_tempo' => 'Belum jatuh tempo',
                                             'belum' => 'Belum bayar',
                                            default => 'Belum ada tagihan',
                                        };
                                        $statusClass = match($student['status']) {
                                             'lunas' => 'is-paid',
                                             'sebagian' => 'is-partial',
                                             'belum_jatuh_tempo' => 'is-upcoming',
                                             'belum' => 'is-unpaid',
                                            default => '',
                                        };
                                    @endphp
                                    <tr
                                        data-student-row
                                        data-search="{{ strtolower($student['nama'] . ' ' . $student['nis'] . ' ' . $student['school_name'] . ' ' . $student['class_name']) }}"
                                        data-status="{{ $student['status'] }}"
                                    >
                                        <td class="billing-student-cell" data-label="Siswa">
                                            <div class="billing-student">
                                                <span class="billing-student-avatar">{{ $student['initial'] }}</span>
                                                <span class="billing-student-copy">
                                                    <strong>{{ $student['nama'] }}</strong>
                                                    <span>NIS {{ $student['nis'] }}</span>
                                                    <span class="billing-student-scope">
                                                        {{ $student['school_name'] }} · {{ $student['class_name'] }}
                                                    </span>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="billing-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td data-label="Tagihan">{{ number_format($student['total_tagihan'], 0, ',', '.') }}</td>
                                        <td data-label="Total nominal"><span class="billing-money">{{ $formatRupiah($student['total_nominal']) }}</span></td>
                                        <td data-label="Dibayar"><span class="billing-money">{{ $formatRupiah($student['total_dibayar']) }}</span></td>
                                        <td data-label="Sisa">
                                            <span class="billing-money {{ $student['sisa_bayar'] > 0 ? 'is-remaining' : 'is-zero' }}">
                                                {{ $formatRupiah($student['sisa_bayar']) }}
                                            </span>
                                        </td>
                                        <td class="billing-action-cell" data-label="Aksi">
                                            <div class="billing-row-actions">
                                                @if($student['total_tagihan'] === 0 && $canGenerate)
                                                    <form method="POST" action="{{ route('tagihan.generate.manual.siswa', $student['id']) }}" data-single-generate-form>
                                                        @csrf
                                                        <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaran?->id }}">
                                                        <button class="billing-row-action" type="submit">
                                                            <i class="fas fa-file-circle-plus"></i>
                                                            <span>Buat tagihan</span>
                                                        </button>
                                                    </form>
                                                @elseif($canPay)
                                                    @php($hasActualOutstanding = (float) ($student['sisa_aktual'] ?? $student['sisa_bayar']) > 0)
                                                    <a class="billing-row-action {{ $hasActualOutstanding ? 'is-primary' : '' }}" href="{{ route('tagihan.proses.siswa', ['siswaId' => $student['id'], 'tahun_ajaran' => $selectedTahunAjaran?->id]) }}">
                                                        <i class="fas {{ $hasActualOutstanding ? 'fa-credit-card' : 'fa-eye' }}"></i>
                                                        <span>{{ $hasActualOutstanding ? 'Bayar' : 'Lihat detail' }}</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="billing-empty" id="billingNoResults" hidden>
                        <div>
                            <i class="fas fa-magnifying-glass"></i>
                            <strong>Siswa tidak ditemukan</strong>
                            <span>Ubah kata pencarian atau pilih status tagihan lain.</span>
                        </div>
                    </div>

                    <footer class="billing-panel-footer">
                        <span><strong id="billingResultCount">{{ $studentRows->count() }}</strong> dari {{ $studentRows->count() }} siswa ditampilkan</span>
                        <span>Gunakan tombol Bayar untuk membuka rincian tagihan siswa.</span>
                    </footer>
                @elseif($sekolahData->isEmpty())
                    <div class="billing-empty">
                        <div>
                            <i class="fas fa-school"></i>
                            <strong>Belum ada sekolah</strong>
                            <span>Tambahkan sekolah agar kelas, siswa, dan tagihan dapat dikelola.</span>
                        </div>
                    </div>
                @else
                    <div class="billing-empty">
                        <div>
                            <i class="fas fa-user-graduate"></i>
                            <strong>Belum ada siswa</strong>
                            <span>Tidak ada siswa pada cakupan {{ strtolower($selectedContext) }}.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

@if($canGenerate)
    <div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="generateModalLabel">Buat tagihan semua siswa</h2>
                        <p class="billing-panel-context">Sistem hanya membuat tagihan dari jenis pembayaran yang sudah diatur.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="billing-modal-copy">Pastikan tiga data berikut sudah benar sebelum melanjutkan:</p>
                    <ul class="billing-checklist">
                        <li><i class="fas fa-circle-check"></i><span>Siswa sudah memiliki sekolah dan kelas.</span></li>
                        <li><i class="fas fa-circle-check"></i><span>SPP sudah ditambahkan sebagai jenis pembayaran bulanan jika diperlukan.</span></li>
                        <li><i class="fas fa-circle-check"></i><span>Nominal, frekuensi, dan target jenis pembayaran sudah diatur.</span></li>
                    </ul>
                    <div class="billing-modal-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Proses dapat membutuhkan beberapa saat. Jangan menutup halaman sampai proses selesai.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="billing-button" data-bs-dismiss="modal">Batal</button>
                    <form id="generateAllBillsForm" action="{{ route('tagihan.generate.manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaran?->id }}">
                        <button class="billing-button is-primary" id="generateAllBillsButton" type="submit">
                            <i class="fas fa-file-circle-plus"></i>
                            <span>Buat tagihan sekarang</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const scopeForm = document.getElementById('billingScopeForm');
    const schoolSelect = document.getElementById('billingSchool');
    const classSelect = document.getElementById('billingClass');
    const academicYearSelect = document.getElementById('billingAcademicYear');
    const searchInput = document.getElementById('billingStudentSearch');
    const clearSearch = document.getElementById('billingSearchClear');
    const rows = [...document.querySelectorAll('[data-student-row]')];
    const filterButtons = [...document.querySelectorAll('[data-billing-filter]')];
    const noResults = document.getElementById('billingNoResults');
    const resultCount = document.getElementById('billingResultCount');
    let activeStatus = 'all';

    schoolSelect?.addEventListener('change', () => {
        if (classSelect) classSelect.disabled = true;
        scopeForm?.submit();
    });

    classSelect?.addEventListener('change', () => scopeForm?.submit());
    academicYearSelect?.addEventListener('change', () => scopeForm?.submit());

    const filterRows = () => {
        const query = searchInput?.value.trim().toLocaleLowerCase('id-ID') ?? '';
        let visibleCount = 0;

        rows.forEach((row) => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const status = row.dataset.status;
            const matchesStatus = activeStatus === 'all'
                || status === activeStatus
                || (activeStatus === 'open' && ['belum', 'sebagian', 'belum_jatuh_tempo'].includes(status));
            const visible = matchesSearch && matchesStatus;

            row.hidden = !visible;
            if (visible) visibleCount++;
        });

        clearSearch?.classList.toggle('is-visible', query.length > 0);
        if (resultCount) resultCount.textContent = visibleCount.toLocaleString('id-ID');
        if (noResults) noResults.hidden = visibleCount !== 0;
    };

    searchInput?.addEventListener('input', filterRows);

    clearSearch?.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.focus();
        filterRows();
    });

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeStatus = button.dataset.billingFilter;
            filterButtons.forEach((item) => item.classList.toggle('is-active', item === button));
            filterRows();
        });
    });

    document.querySelectorAll('[data-single-generate-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Buat tagihan untuk siswa ini sekarang?')) {
                event.preventDefault();
            }
        });
    });

    const generateForm = document.getElementById('generateAllBillsForm');
    const generateButton = document.getElementById('generateAllBillsButton');

    generateForm?.addEventListener('submit', () => {
        generateButton.disabled = true;
        generateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sedang membuat...</span>';
    });
});
</script>
@endsection
