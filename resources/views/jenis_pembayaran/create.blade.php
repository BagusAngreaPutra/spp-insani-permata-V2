@extends('layouts.app')
@include('layouts.sidebar')

@php
    $editingJenis = $jenis ?? null;
    $isEdit = $editingJenis !== null;
    $oldType = old('tipe', $editingJenis?->tipe);
    $oldDueDate = old('jatuh_tempo', $editingJenis?->jatuh_tempo);
    $selectedSchool = old('sekolah_id', $editingJenis?->sekolah_id);
    $selectedAcademicYear = old('tahun_ajaran_id', $editingJenis?->tahun_ajaran_id);
    $selectedTarget = old('target_type', $editingJenis?->target_type ?? 'all');
    $selectedStudentIds = old('siswa_ids', $editingJenis?->siswa?->pluck('id')->all() ?? []);
    $selectedClassIds = old('kelas_ids', $editingJenis?->kelas?->pluck('id')->all() ?? []);
    $submissionToken = old('submission_token', (string) \Illuminate\Support\Str::uuid());
    $oldDueDay = $oldType === 'bulanan' ? $oldDueDate : null;
    $oldDueMonth = $oldType === 'semester' ? $oldDueDate : null;

    if (is_string($oldDueDate) && preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $oldDueDate, $dueParts)) {
        $oldDueDay = (int) $dueParts[2];
        $oldDueMonth = (int) $dueParts[1];
    }
@endphp

@section('content')
@push('page-styles')
<style>
    .payment-type-form-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .payment-type-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 18px;
    }

    .payment-type-title-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .payment-type-title-icon,
    .payment-section-icon {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 10px;
    }

    .payment-type-title-icon {
        width: 48px;
        height: 48px;
        color: #4f46e5;
        background: #eef0ff;
        border: 1px solid #cfd3ff;
        font-size: 19px;
    }

    .payment-type-eyebrow {
        display: block;
        margin-bottom: 4px;
        color: #7c3aed;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .payment-type-page-header h1 {
        margin: 0;
        color: #17211c;
        font-size: 25px;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    .payment-type-page-header p {
        margin: 5px 0 0;
        color: #667085;
        font-size: 11px;
        line-height: 1.55;
    }

    .payment-back-button {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 15px;
        color: #fff;
        background: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 6px 14px rgba(37, 99, 235, .18);
    }

    .payment-form-alert {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 16px;
        padding: 13px 15px;
        color: #b42318;
        background: #fff5f5;
        border: 1px solid #fecaca;
        border-radius: 10px;
        font-size: 10px;
        line-height: 1.6;
    }

    .payment-form-alert i { margin-top: 2px; }
    .payment-form-alert ul { margin: 0; padding-left: 17px; }

    .payment-type-form {
        display: grid;
        gap: 14px;
    }

    .payment-form-section {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e1e6e3;
        border-radius: 13px;
        box-shadow: 0 5px 18px rgba(16, 24, 40, .045);
    }

    .payment-section-head {
        display: flex;
        align-items: center;
        gap: 11px;
        min-height: 64px;
        padding: 13px 18px;
        background: #fbfcfc;
        border-bottom: 1px solid #e7ebe9;
    }

    .payment-section-icon {
        width: 36px;
        height: 36px;
        line-height: 1;
        text-align: center;
        font-size: 14px;
    }

    .payment-type-title-icon > i,
    .payment-section-icon > i {
        display: block;
        width: auto;
        margin: 0;
        line-height: 1;
    }

    .payment-section-icon.is-indigo { color: #4f46e5; background: #eef0ff; border: 1px solid #d6d9ff; }
    .payment-section-icon.is-orange { color: #ea580c; background: #fff3e8; border: 1px solid #fed7aa; }
    .payment-section-icon.is-pink { color: #db2777; background: #fdf0f7; border: 1px solid #fbcfe8; }

    .payment-section-head > div > strong,
    .payment-section-head > div > span { display: block; }
    .payment-section-head > div > strong { color: #17211c; font-size: 12px; font-weight: 800; }
    .payment-section-head > div > span { margin-top: 3px; color: #7b8580; font-size: 9px; }

    .payment-section-body { padding: 18px; }
    .payment-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; }
    .payment-field.is-full { grid-column: 1 / -1; }
    .payment-field.is-wide { grid-column: span 2; }

    .payment-field label,
    .payment-subfield-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0 0 7px;
        color: #344054;
        font-size: 10px;
        font-weight: 750;
    }

    .payment-field label i { width: 13px; color: #6673e8; text-align: center; }
    .payment-required { color: #e11d48; }

    .payment-field :is(input, select) {
        width: 100%;
        min-height: 43px;
        padding: 0 12px;
        color: #182230;
        background: #fff;
        border: 1px solid #d5dbe0;
        border-radius: 8px;
        font-size: 11px;
        outline: none;
        transition: border-color .18s, box-shadow .18s;
    }

    .payment-field :is(input, select):focus {
        border-color: #7c83f5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .1);
    }

    .payment-field-help {
        display: block;
        margin-top: 6px;
        color: #98a2b3;
        font-size: 8.5px;
        line-height: 1.5;
    }

    .payment-field-error {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 6px;
        color: #d92d20;
        font-size: 8.5px;
        font-weight: 650;
    }

    .payment-schedule-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
        gap: 15px;
        align-items: stretch;
    }

    .payment-schedule-note {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        min-height: 100%;
        padding: 14px;
        color: #475467;
        background: #f5f8ff;
        border: 1px solid #dce4ff;
        border-radius: 9px;
        font-size: 9px;
        line-height: 1.6;
    }

    .payment-schedule-note i { margin-top: 2px; color: #4f46e5; font-size: 14px; }
    .payment-schedule-note strong { display: block; margin-bottom: 3px; color: #253247; font-size: 10px; }
    .payment-due-field[hidden] { display: none !important; }

    .payment-target-options {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .payment-target-option {
        position: relative;
        min-height: 92px;
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) 16px;
        align-items: center;
        gap: 10px;
        padding: 13px;
        color: #475467;
        background: #fff;
        border: 1px solid #dfe4e8;
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .18s, background .18s, box-shadow .18s;
    }

    .payment-target-option:hover { border-color: #bfc6ff; background: #fafaff; }
    .payment-target-option:has(input:checked) { color: #3730a3; background: #f3f4ff; border-color: #9ca3ff; box-shadow: 0 0 0 2px rgba(79, 70, 229, .07); }
    .payment-target-option input { position: absolute; opacity: 0; pointer-events: none; }

    .payment-target-icon {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        color: #667085;
        background: #f2f4f7;
        border-radius: 8px;
    }

    body:has(.app-sidebar) .main-content .payment-target-option .payment-target-icon > i {
        display: inline-block !important;
        font-size: 13px;
    }
    body:has(.app-sidebar) .main-content .payment-target-option > .payment-target-check {
        width: 16px !important;
        margin: 0 !important;
        color: #c4cbd2 !important;
        background: transparent !important;
        font-size: 12px;
        opacity: .24 !important;
    }

    .payment-target-option:has(input:checked) .payment-target-icon { color: #4f46e5; background: #e6e8ff; }
    .payment-target-copy strong, .payment-target-copy span { display: block; }
    .payment-target-copy strong { color: #253247; font-size: 10.5px; }
    .payment-target-copy span { margin-top: 4px; font-size: 8px; line-height: 1.45; }
    body:has(.app-sidebar) .main-content .payment-target-option:has(input:checked) > .payment-target-check {
        color: #4f46e5 !important;
        opacity: 1 !important;
    }

    .payment-target-panel {
        margin-top: 14px;
        padding: 15px;
        background: #fafbfc;
        border: 1px solid #e2e6ea;
        border-radius: 10px;
    }
    .payment-target-panel[hidden] { display: none !important; }

    .payment-target-toolbar {
        display: grid;
        grid-template-columns: minmax(180px, .7fr) minmax(220px, 1fr) auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 11px;
    }

    .payment-target-toolbar .payment-field :is(input, select) { min-height: 40px; }
    .payment-target-toolbar-actions { display: flex; gap: 7px; }

    .payment-select-button {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 11px;
        color: #4f46e5;
        background: #eef0ff;
        border: 1px solid #cfd3ff;
        border-radius: 8px;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }

    .payment-selection-list {
        max-height: 290px;
        overflow: auto;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
        padding: 8px;
        background: #fff;
        border: 1px solid #e1e5e9;
        border-radius: 9px;
    }

    .payment-selection-item {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 48px;
        padding: 9px 10px;
        color: #475467;
        background: #fbfcfd;
        border: 1px solid #edf0f2;
        border-radius: 8px;
        cursor: pointer;
    }

    .payment-selection-item:has(input:checked) { color: #3730a3; background: #f3f4ff; border-color: #cbd0ff; }
    .payment-selection-item input { flex: 0 0 auto; }
    .payment-selection-copy { min-width: 0; }
    .payment-selection-copy strong, .payment-selection-copy span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .payment-selection-copy strong { color: #253247; font-size: 9.5px; }
    .payment-selection-copy span { margin-top: 3px; color: #98a2b3; font-size: 8px; }

    .payment-selection-empty {
        grid-column: 1 / -1;
        min-height: 110px;
        display: grid;
        place-items: center;
        gap: 7px;
        padding: 20px;
        color: #98a2b3;
        font-size: 9px;
        text-align: center;
    }
    .payment-selection-empty i { color: #c3cbd3; font-size: 22px; }

    .payment-selection-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 9px;
        color: #667085;
        font-size: 8.5px;
    }
    .payment-selection-summary strong { color: #4f46e5; }

    .payment-form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 9px;
        padding: 16px 18px;
        background: #fafbfc;
        border-top: 1px solid #e4e7ec;
    }

    .payment-action-button {
        min-width: 122px;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 16px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }
    .payment-action-button.is-cancel { color: #fff; background: #e11d48; border: 1px solid #e11d48; }
    .payment-action-button.is-save { color: #fff; background: #16a34a; border: 1px solid #16a34a; box-shadow: 0 6px 14px rgba(22, 163, 74, .17); }
    .payment-action-button.is-save:disabled { opacity: .68; cursor: wait; box-shadow: none; }

    @media (max-width: 900px) {
        .payment-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .payment-field.is-wide { grid-column: auto; }
        .payment-schedule-grid { grid-template-columns: 1fr; }
        .payment-target-options { grid-template-columns: 1fr; }
        .payment-target-toolbar { grid-template-columns: 1fr 1fr; }
        .payment-target-toolbar-actions { grid-column: 1 / -1; }
    }

    @media (max-width: 700px) {
        .payment-type-page-header { align-items: flex-start; flex-direction: column; }
        .payment-back-button { width: 100%; }
        .payment-form-grid, .payment-selection-list, .payment-target-toolbar { grid-template-columns: 1fr; }
        .payment-field.is-full, .payment-field.is-wide { grid-column: auto; }
        .payment-target-toolbar-actions { grid-column: auto; display: grid; grid-template-columns: 1fr 1fr; }
        .payment-form-actions { align-items: stretch; flex-direction: column-reverse; }
        .payment-action-button { width: 100%; }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="payment-type-form-page">
            <div class="payment-type-page-header">
                <div class="payment-type-title-wrap">
                    <span class="payment-type-title-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div>
                        <span class="payment-type-eyebrow">Pengaturan tagihan</span>
                        <h1>{{ $isEdit ? 'Edit Jenis Pembayaran' : 'Tambah Jenis Pembayaran' }}</h1>
                        <p>{{ $isEdit ? 'Perbarui komponen biaya, tahun ajaran, jadwal, dan target penerima tagihan.' : 'Buat komponen biaya, tahun ajaran, jadwal penagihan, dan tentukan siswa penerimanya.' }}</p>
                    </div>
                </div>
                <a href="{{ route('jenis_pembayaran.index') }}" class="payment-back-button">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            @if ($errors->any())
                <div class="payment-form-alert" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <div>
                        <strong>Data belum dapat disimpan.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form id="paymentTypeForm" class="payment-type-form" action="{{ $isEdit ? route('jenis_pembayaran.update', $editingJenis->id) : route('jenis_pembayaran.store') }}" method="POST">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @else
                    <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                @endif

                <section class="payment-form-section">
                    <div class="payment-section-head">
                        <span class="payment-section-icon is-indigo"><i class="fas fa-receipt"></i></span>
                        <div><strong>Informasi Pembayaran</strong><span>Identitas dasar dan nominal yang akan ditagihkan.</span></div>
                    </div>
                    <div class="payment-section-body">
                        <div class="payment-form-grid">
                            <div class="payment-field">
                                <label for="sekolah_id"><i class="fas fa-school"></i>Sekolah <span class="payment-required">*</span></label>
                                <select name="sekolah_id" id="sekolah_id" required>
                                    <option value="">Pilih sekolah</option>
                                    @foreach($sekolah as $item)
                                        <option value="{{ $item->id }}" {{ (string) $selectedSchool === (string) $item->id ? 'selected' : '' }}>{{ $item->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                                @error('sekolah_id')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="payment-field is-wide">
                                <label for="nama_pembayaran"><i class="fas fa-tag"></i>Nama Pembayaran <span class="payment-required">*</span></label>
                                <input id="nama_pembayaran" type="text" name="nama_pembayaran" value="{{ old('nama_pembayaran', $editingJenis?->nama_pembayaran) }}" placeholder="Contoh: Uang kegiatan semester" autocomplete="off" required>
                                @error('nama_pembayaran')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="payment-field">
                                <label for="tipe"><i class="fas fa-repeat"></i>Frekuensi Tagihan <span class="payment-required">*</span></label>
                                <select name="tipe" id="tipe" required>
                                    <option value="">Pilih frekuensi</option>
                                    <option value="sekali" {{ $oldType === 'sekali' ? 'selected' : '' }}>Sekali bayar</option>
                                    <option value="bulanan" {{ $oldType === 'bulanan' ? 'selected' : '' }}>Bulanan · 12 kali setahun</option>
                                    <option value="setahun" {{ $oldType === 'setahun' ? 'selected' : '' }}>Tahunan · 1 kali setahun</option>
                                    <option value="semester" {{ $oldType === 'semester' ? 'selected' : '' }}>Semester · 2 kali setahun</option>
                                </select>
                                @error('tipe')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="payment-field">
                                <label for="tahun_ajaran_id"><i class="fas fa-calendar-days"></i>Tahun Ajaran <span class="payment-required">*</span></label>
                                <select name="tahun_ajaran_id" id="tahun_ajaran_id" required>
                                    <option value="">Pilih tahun ajaran</option>
                                    @foreach($tahunAjaran as $tahun)
                                        @php($yearBounds = $tahun->periodBounds())
                                        <option value="{{ $tahun->id }}"
                                                data-period-start="{{ $yearBounds ? $yearBounds[0] . '-07-01' : '' }}"
                                                data-period-end="{{ $yearBounds ? $yearBounds[1] . '-06-30' : '' }}"
                                                {{ (string) $selectedAcademicYear === (string) $tahun->id ? 'selected' : '' }}>
                                            {{ $tahun->label }}{{ $tahun->aktif ? ' · Aktif' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="payment-field-help">Menentukan periode Juli–Juni dan nama pada tagihan.</span>
                                @error('tahun_ajaran_id')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="payment-field">
                                <label for="nominal-pembayaran"><i class="fas fa-coins"></i>Nominal <span class="payment-required">*</span></label>
                                <input id="nominal-pembayaran" type="number" name="nominal" data-rupiah min="0" max="2147483647" step="1" value="{{ old('nominal', $editingJenis?->nominal) }}" placeholder="Rp0" required>
                                <span class="payment-field-help">Masukkan nominal untuk satu periode tagihan.</span>
                                <span id="nominal-warning" class="payment-field-error" hidden><i class="fas fa-circle-exclamation"></i>Nominal maksimal Rp2.147.483.647.</span>
                                @error('nominal')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="payment-form-section">
                    <div class="payment-section-head">
                        <span class="payment-section-icon is-orange"><i class="fas fa-calendar-days"></i></span>
                        <div><strong>Jadwal Penagihan</strong><span>Atur tanggal atau periode jatuh tempo sesuai frekuensi.</span></div>
                    </div>
                    <div class="payment-section-body">
                        <div class="payment-schedule-grid">
                            <div>
                                <div class="payment-field payment-due-field" data-due-type="empty">
                                    <label><i class="fas fa-calendar-check"></i>Jatuh Tempo</label>
                                    <input type="text" value="Pilih frekuensi tagihan terlebih dahulu" disabled>
                                </div>

                                <div class="payment-field payment-due-field" data-due-type="sekali" hidden>
                                    <label for="jatuh_tempo_sekali"><i class="fas fa-calendar-day"></i>Tanggal Jatuh Tempo <span class="payment-required">*</span></label>
                                    <input id="jatuh_tempo_sekali" type="date" name="jatuh_tempo" value="{{ $oldType === 'sekali' ? $oldDueDate : '' }}" disabled required>
                                </div>

                                <div class="payment-field payment-due-field" data-due-type="bulanan" hidden>
                                    <label for="jatuh_tempo_bulanan"><i class="fas fa-calendar-day"></i>Tanggal Setiap Bulan <span class="payment-required">*</span></label>
                                    <select id="jatuh_tempo_bulanan" name="jatuh_tempo" disabled required>
                                        <option value="">Pilih tanggal</option>
                                        @for ($day = 1; $day <= 28; $day++)
                                            <option value="{{ $day }}" {{ (string) $oldDueDay === (string) $day ? 'selected' : '' }}>Tanggal {{ $day }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="payment-field payment-due-field" data-due-type="setahun" hidden>
                                    <label for="jatuh_tempo_setahun"><i class="fas fa-calendar-day"></i>Tanggal Jatuh Tempo Tahunan <span class="payment-required">*</span></label>
                                    <input id="jatuh_tempo_setahun" type="date" name="jatuh_tempo" value="{{ $oldType === 'setahun' ? $oldDueDate : '' }}" disabled required>
                                </div>

                                <div class="payment-field payment-due-field" data-due-type="semester" hidden>
                                    <label for="jatuh_tempo_semester"><i class="fas fa-calendar-day"></i>Pasangan Bulan Semester <span class="payment-required">*</span></label>
                                    <select id="jatuh_tempo_semester" name="jatuh_tempo" disabled required>
                                        <option value="">Pilih pasangan bulan</option>
                                        @foreach([1 => 'Januari dan Juli', 2 => 'Februari dan Agustus', 3 => 'Maret dan September', 4 => 'April dan Oktober', 5 => 'Mei dan November', 6 => 'Juni dan Desember'] as $month => $label)
                                            <option value="{{ $month }}" {{ (string) $oldDueMonth === (string) $month ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('jatuh_tempo')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="payment-schedule-note">
                                <i class="fas fa-circle-info"></i>
                                <div><strong id="schedule-note-title">Pilih frekuensi tagihan</strong><span id="schedule-note-copy">Sistem akan menyesuaikan pilihan jatuh tempo dan jumlah tagihan yang dibuat.</span></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="payment-form-section">
                    <div class="payment-section-head">
                        <span class="payment-section-icon is-pink"><i class="fas fa-users"></i></span>
                        <div><strong>Target Pembayaran</strong><span>Pilih siapa saja yang akan menerima tagihan ini.</span></div>
                    </div>
                    <div class="payment-section-body">
                        <div class="payment-target-options">
                            <label class="payment-target-option">
                                <input type="radio" name="target_type" value="all" {{ $selectedTarget === 'all' ? 'checked' : '' }}>
                                <span class="payment-target-icon"><i class="fas fa-users"></i></span>
                                <span class="payment-target-copy"><strong>Semua Siswa</strong><span>Seluruh siswa pada sekolah terpilih.</span></span>
                                <i class="fas fa-circle-check payment-target-check"></i>
                            </label>
                            <label class="payment-target-option">
                                <input type="radio" name="target_type" value="specific_students" {{ $selectedTarget === 'specific_students' ? 'checked' : '' }}>
                                <span class="payment-target-icon"><i class="fas fa-user-check"></i></span>
                                <span class="payment-target-copy"><strong>Siswa Tertentu</strong><span>Pilih siswa secara individual.</span></span>
                                <i class="fas fa-circle-check payment-target-check"></i>
                            </label>
                            <label class="payment-target-option">
                                <input type="radio" name="target_type" value="specific_classes" {{ $selectedTarget === 'specific_classes' ? 'checked' : '' }}>
                                <span class="payment-target-icon"><i class="fas fa-users-rectangle"></i></span>
                                <span class="payment-target-copy"><strong>Kelas Tertentu</strong><span>Pilih satu atau beberapa kelas.</span></span>
                                <i class="fas fa-circle-check payment-target-check"></i>
                            </label>
                        </div>

                        <div id="studentsTargetPanel" class="payment-target-panel" hidden>
                            <div class="payment-target-toolbar">
                                <div class="payment-field">
                                    <span class="payment-subfield-label">Filter Kelas</span>
                                    <select id="studentClassFilter"><option value="">Semua kelas</option></select>
                                </div>
                                <div class="payment-field">
                                    <span class="payment-subfield-label">Cari Siswa</span>
                                    <input id="studentSearch" type="search" placeholder="Cari nama atau NIS..." autocomplete="off">
                                </div>
                                <div class="payment-target-toolbar-actions">
                                    <button id="toggleVisibleStudents" class="payment-select-button" type="button"><i class="fas fa-check-double"></i>Pilih yang terlihat</button>
                                </div>
                            </div>
                            <div id="studentsList" class="payment-selection-list"></div>
                            <div class="payment-selection-summary"><span>Pilihan tersimpan meskipun filter diubah.</span><strong><span id="selectedStudentCount">0</span> siswa dipilih</strong></div>
                        </div>

                        <div id="classesTargetPanel" class="payment-target-panel" hidden>
                            <div class="payment-target-toolbar" style="grid-template-columns: 1fr auto;">
                                <div><span class="payment-subfield-label">Daftar Kelas</span><span class="payment-field-help">Kelas mengikuti sekolah yang dipilih pada bagian informasi pembayaran.</span></div>
                                <div class="payment-target-toolbar-actions">
                                    <button id="toggleAllClasses" class="payment-select-button" type="button"><i class="fas fa-check-double"></i>Pilih semua kelas</button>
                                </div>
                            </div>
                            <div id="classesList" class="payment-selection-list"></div>
                            <div class="payment-selection-summary"><span>Pilih minimal satu kelas.</span><strong><span id="selectedClassCount">0</span> kelas dipilih</strong></div>
                        </div>

                        @error('siswa_ids')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                        @error('kelas_ids')<span class="payment-field-error"><i class="fas fa-circle-exclamation"></i>{{ $message }}</span>@enderror
                    </div>

                    <div class="payment-form-actions">
                        <a href="{{ route('jenis_pembayaran.index') }}" class="payment-action-button is-cancel"><i class="fas fa-xmark"></i>Batal</a>
                        <button id="savePaymentType" type="submit" class="payment-action-button is-save"><i class="fas fa-floppy-disk"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pembayaran' }}</button>
                    </div>
                </section>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('paymentTypeForm');
    const typeSelect = document.getElementById('tipe');
    const dueFields = Array.from(document.querySelectorAll('[data-due-type]'));
    const scheduleTitle = document.getElementById('schedule-note-title');
    const scheduleCopy = document.getElementById('schedule-note-copy');
    const nominalInput = document.getElementById('nominal-pembayaran');
    const nominalWarning = document.getElementById('nominal-warning');
    const saveButton = document.getElementById('savePaymentType');
    const saveButtonLabel = saveButton.innerHTML;
    const schoolSelect = document.getElementById('sekolah_id');
    const academicYearSelect = document.getElementById('tahun_ajaran_id');
    const targetRadios = Array.from(document.querySelectorAll('input[name="target_type"]'));
    const studentsPanel = document.getElementById('studentsTargetPanel');
    const classesPanel = document.getElementById('classesTargetPanel');
    const studentsList = document.getElementById('studentsList');
    const classesList = document.getElementById('classesList');
    const classFilter = document.getElementById('studentClassFilter');
    const studentSearch = document.getElementById('studentSearch');
    const studentCount = document.getElementById('selectedStudentCount');
    const classCount = document.getElementById('selectedClassCount');
    const selectedStudents = new Set(@json(array_map('strval', (array) $selectedStudentIds)));
    const selectedClasses = new Set(@json(array_map('strval', (array) $selectedClassIds)));
    const endpointBase = @json(url('/jenis-pembayaran/get-data-by-sekolah'));
    let students = [];
    let classes = [];

    const scheduleNotes = {
        empty: ['Pilih frekuensi tagihan', 'Sistem akan menyesuaikan pilihan jatuh tempo dan jumlah tagihan yang dibuat.'],
        sekali: ['Satu tagihan', 'Tagihan dibuat satu kali dengan tanggal jatuh tempo yang Anda tentukan.'],
        bulanan: ['12 tagihan bulanan', 'Sistem membuat tagihan setiap bulan pada tanggal yang sama.'],
        setahun: ['Satu tagihan tahunan', 'Tagihan dibuat satu kali untuk periode tahun berjalan.'],
        semester: ['Dua tagihan semester', 'Sistem membuat dua tagihan dengan jarak enam bulan.']
    };

    function toggleDueField() {
        const selectedType = typeSelect.value || 'empty';
        dueFields.forEach(function (field) {
            const active = field.dataset.dueType === selectedType;
            field.hidden = !active;
            field.querySelectorAll('input, select').forEach(function (control) {
                control.disabled = !active || selectedType === 'empty';
            });
        });
        const note = scheduleNotes[selectedType] || scheduleNotes.empty;
        scheduleTitle.textContent = note[0];
        scheduleCopy.textContent = note[1];
    }

    function syncDueDateRange() {
        const selectedOption = academicYearSelect?.selectedOptions?.[0];
        const periodStart = selectedOption?.dataset.periodStart || '';
        const periodEnd = selectedOption?.dataset.periodEnd || '';

        ['jatuh_tempo_sekali', 'jatuh_tempo_setahun'].forEach(function (id) {
            const input = document.getElementById(id);
            if (!input) return;

            input.min = periodStart;
            input.max = periodEnd;

            if (input.value && periodStart && periodEnd && (input.value < periodStart || input.value > periodEnd)) {
                input.value = '';
            }
        });

        if (periodStart && periodEnd && typeSelect.value) {
            const current = scheduleNotes[typeSelect.value] || scheduleNotes.empty;
            scheduleTitle.textContent = current[0];
            scheduleCopy.textContent = `${current[1]} Tanggal mengikuti ${selectedOption.textContent.trim()}.`;
        }
    }

    function toggleTargetPanels() {
        const selected = document.querySelector('input[name="target_type"]:checked')?.value || 'all';
        studentsPanel.hidden = selected !== 'specific_students';
        classesPanel.hidden = selected !== 'specific_classes';
    }

    function emptyState(container, icon, message) {
        container.replaceChildren();
        const state = document.createElement('div');
        state.className = 'payment-selection-empty';
        state.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
        container.appendChild(state);
    }

    function updateCounts() {
        studentCount.textContent = selectedStudents.size.toLocaleString('id-ID');
        classCount.textContent = selectedClasses.size.toLocaleString('id-ID');
    }

    function classLabel(item) {
        const name = String(item.nama_kelas || '').trim();
        const level = item.label_tingkat || (item.tingkat === null ? 'Tanpa tingkat' : `Tingkat ${item.tingkat}`);
        return name && name !== '-' ? `${level} · ${name}` : level;
    }

    function renderStudents() {
        if (!schoolSelect.value) {
            emptyState(studentsList, 'fa-school', 'Pilih sekolah terlebih dahulu.');
            updateCounts();
            return;
        }

        const classId = classFilter.value;
        const keyword = studentSearch.value.trim().toLocaleLowerCase('id-ID');
        const visibleStudents = students.filter(function (student) {
            const matchesClass = !classId || String(student.kelas_id) === String(classId);
            const haystack = `${student.nama || ''} ${student.nis || ''}`.toLocaleLowerCase('id-ID');
            return matchesClass && (!keyword || haystack.includes(keyword));
        });

        studentsList.replaceChildren();
        if (!visibleStudents.length) {
            emptyState(studentsList, 'fa-user-slash', students.length ? 'Tidak ada siswa yang sesuai dengan filter.' : 'Belum ada siswa pada sekolah ini.');
            updateCounts();
            return;
        }

        visibleStudents.forEach(function (student) {
            const label = document.createElement('label');
            label.className = 'payment-selection-item';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'siswa_ids[]';
            checkbox.value = student.id;
            checkbox.checked = selectedStudents.has(String(student.id));
            checkbox.addEventListener('change', function () {
                checkbox.checked ? selectedStudents.add(String(student.id)) : selectedStudents.delete(String(student.id));
                updateCounts();
            });

            const copy = document.createElement('span');
            copy.className = 'payment-selection-copy';
            const name = document.createElement('strong');
            name.textContent = student.nama;
            const detail = document.createElement('span');
            detail.textContent = `${student.nis} · ${student.kelas_nama || 'Kelas belum diatur'}`;
            copy.append(name, detail);
            label.append(checkbox, copy);
            studentsList.appendChild(label);
        });
        updateCounts();
    }

    function renderClasses() {
        if (!schoolSelect.value) {
            emptyState(classesList, 'fa-school', 'Pilih sekolah terlebih dahulu.');
            updateCounts();
            return;
        }

        classesList.replaceChildren();
        if (!classes.length) {
            emptyState(classesList, 'fa-users-slash', 'Belum ada kelas pada sekolah ini.');
            updateCounts();
            return;
        }

        classes.forEach(function (item) {
            const label = document.createElement('label');
            label.className = 'payment-selection-item';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'kelas_ids[]';
            checkbox.value = item.id;
            checkbox.checked = selectedClasses.has(String(item.id));
            checkbox.addEventListener('change', function () {
                checkbox.checked ? selectedClasses.add(String(item.id)) : selectedClasses.delete(String(item.id));
                updateCounts();
            });

            const copy = document.createElement('span');
            copy.className = 'payment-selection-copy';
            const name = document.createElement('strong');
            name.textContent = classLabel(item);
            const detail = document.createElement('span');
            detail.textContent = 'Target berdasarkan kelas';
            copy.append(name, detail);
            label.append(checkbox, copy);
            classesList.appendChild(label);
        });
        updateCounts();
    }

    function populateClassFilter() {
        classFilter.replaceChildren(new Option('Semua kelas', ''));
        classes.forEach(function (item) {
            classFilter.appendChild(new Option(classLabel(item), item.id));
        });
    }

    async function loadSchoolData(preserveOldSelection) {
        const schoolId = schoolSelect.value;
        if (!preserveOldSelection) {
            selectedStudents.clear();
            selectedClasses.clear();
        }

        if (!schoolId) {
            students = [];
            classes = [];
            populateClassFilter();
            renderStudents();
            renderClasses();
            return;
        }

        emptyState(studentsList, 'fa-spinner fa-spin', 'Memuat data siswa...');
        emptyState(classesList, 'fa-spinner fa-spin', 'Memuat data kelas...');

        try {
            const response = await fetch(`${endpointBase}/${encodeURIComponent(schoolId)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('Data sekolah gagal dimuat.');
            const data = await response.json();
            students = Array.isArray(data.siswa) ? data.siswa : [];
            classes = Array.isArray(data.kelas) ? data.kelas : [];
            populateClassFilter();
            renderStudents();
            renderClasses();
        } catch (error) {
            students = [];
            classes = [];
            emptyState(studentsList, 'fa-triangle-exclamation', 'Data siswa gagal dimuat. Silakan coba kembali.');
            emptyState(classesList, 'fa-triangle-exclamation', 'Data kelas gagal dimuat. Silakan coba kembali.');
        }
    }

    function rawMoneyValue(input) {
        if (window.PermataRupiah) return Number(window.PermataRupiah.raw(input.dataset.rupiahRaw ?? input.value) || 0);
        return Number(String(input.value || '').replace(/[^0-9]/g, '') || 0);
    }

    function validateNominal() {
        const tooLarge = rawMoneyValue(nominalInput) > 2147483647;
        nominalWarning.hidden = !tooLarge;
        nominalInput.classList.toggle('is-invalid', tooLarge);
        saveButton.disabled = tooLarge;
        return !tooLarge;
    }

    function syncHiddenSelections(name, selections, visibleContainer) {
        form.querySelectorAll(`[data-preserved-selection="${name}"]`).forEach(function (input) {
            input.remove();
        });

        const visibleValues = new Set(
            Array.from(visibleContainer.querySelectorAll(`input[name="${name}[]"]:checked`))
                .map(function (input) { return String(input.value); })
        );

        selections.forEach(function (value) {
            if (visibleValues.has(String(value))) return;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `${name}[]`;
            input.value = value;
            input.dataset.preservedSelection = name;
            form.appendChild(input);
        });
    }

    typeSelect.addEventListener('change', function () {
        toggleDueField();
        syncDueDateRange();
    });
    academicYearSelect?.addEventListener('change', syncDueDateRange);
    targetRadios.forEach(function (radio) { radio.addEventListener('change', toggleTargetPanels); });
    schoolSelect.addEventListener('change', function () { loadSchoolData(false); });
    classFilter.addEventListener('change', renderStudents);
    studentSearch.addEventListener('input', renderStudents);
    nominalInput.addEventListener('input', validateNominal);

    document.getElementById('toggleVisibleStudents').addEventListener('click', function () {
        const visible = Array.from(studentsList.querySelectorAll('input[name="siswa_ids[]"]'));
        const shouldSelect = visible.some(function (input) { return !input.checked; });
        visible.forEach(function (input) {
            input.checked = shouldSelect;
            shouldSelect ? selectedStudents.add(String(input.value)) : selectedStudents.delete(String(input.value));
        });
        updateCounts();
    });

    document.getElementById('toggleAllClasses').addEventListener('click', function () {
        const inputs = Array.from(classesList.querySelectorAll('input[name="kelas_ids[]"]'));
        const shouldSelect = inputs.some(function (input) { return !input.checked; });
        inputs.forEach(function (input) {
            input.checked = shouldSelect;
            shouldSelect ? selectedClasses.add(String(input.value)) : selectedClasses.delete(String(input.value));
        });
        updateCounts();
    });

    form.addEventListener('submit', function (event) {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        if (!validateNominal()) {
            event.preventDefault();
            nominalInput.focus();
            return;
        }

        const targetType = document.querySelector('input[name="target_type"]:checked')?.value;
        if (targetType === 'specific_students' && selectedStudents.size === 0) {
            event.preventDefault();
            window.alert('Pilih minimal satu siswa sebagai target pembayaran.');
            return;
        }
        if (targetType === 'specific_classes' && selectedClasses.size === 0) {
            event.preventDefault();
            window.alert('Pilih minimal satu kelas sebagai target pembayaran.');
            return;
        }

        syncHiddenSelections('siswa_ids', selectedStudents, studentsList);
        syncHiddenSelections('kelas_ids', selectedClasses, classesList);

        form.dataset.submitting = 'true';
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Menyimpan...';
    });

    window.addEventListener('pageshow', function () {
        form.dataset.submitting = 'false';
        saveButton.disabled = false;
        saveButton.innerHTML = saveButtonLabel;
    });

    toggleDueField();
    syncDueDateRange();
    toggleTargetPanels();
    updateCounts();
    loadSchoolData(true);
});
</script>
@endsection
