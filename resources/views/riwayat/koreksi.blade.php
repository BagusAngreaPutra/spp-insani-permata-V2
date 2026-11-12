@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Koreksi Pembayaran')

@section('content')
@php
    $formatRupiah = static fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $firstPayment = $payments->first();
    $studentGroups = $students->groupBy(fn ($student) => $student->sekolah?->nama_sekolah ?? 'Sekolah belum diatur');
@endphp

@push('page-styles')
<style>
    .correction-page,
    .correction-page * { box-sizing: border-box; }
    .correction-page { display: grid; gap: 18px; min-width: 0; }
    .correction-heading,
    .correction-heading-actions,
    .correction-reference,
    .correction-summary,
    .correction-form-actions,
    .correction-alert { display: flex; align-items: center; }
    .correction-heading { justify-content: space-between; gap: 18px; }
    .correction-eyebrow { margin: 0 0 4px; color: #8b5cf6; font-size: 10px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .correction-title { margin: 0; color: #101828; font-size: clamp(25px, 2.6vw, 32px); font-weight: 700; letter-spacing: -.04em; }
    .correction-subtitle { margin: 7px 0 0; color: #667085; font-size: 12px; }
    .correction-button {
        display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 7px !important;
        min-height: 38px !important; margin: 0 !important; padding: 0 13px !important;
        color: #344054 !important; background: #fff !important; border: 1px solid #d0d5dd !important;
        border-radius: 7px !important; box-shadow: none !important; font: inherit !important;
        font-size: 11px !important; font-weight: 700 !important; text-decoration: none !important; cursor: pointer !important;
    }
    .correction-button.is-primary { color: #fff !important; background: #1d6b4c !important; border-color: #1d6b4c !important; }
    .correction-button.is-danger { color: #b42318 !important; background: #fff !important; border-color: #fecdca !important; }
    .correction-panel { overflow: hidden; background: #fff; border: 1px solid #e4e7ec; border-radius: 10px; }
    .correction-panel-head { display: flex; justify-content: space-between; gap: 16px; padding: 17px 18px; border-bottom: 1px solid #e4e7ec; }
    .correction-panel-head h2 { margin: 0; color: #101828; font-size: 14px; font-weight: 700; }
    .correction-panel-head p { margin: 4px 0 0; color: #667085; font-size: 10px; }
    .correction-reference { align-self: flex-start; gap: 7px; padding: 7px 9px; color: #5925dc; background: #f4f3ff; border: 1px solid #d9d6fe; border-radius: 7px; font-size: 10px; font-weight: 700; white-space: nowrap; }
    .correction-summary { align-items: stretch; padding: 0 18px; border-bottom: 1px solid #e4e7ec; }
    .correction-summary-item { flex: 1 1 0; min-width: 0; padding: 14px 12px 14px 0; }
    .correction-summary-item + .correction-summary-item { padding-left: 16px; border-left: 1px solid #e4e7ec; }
    .correction-summary-item span,
    .correction-summary-item strong { display: block; }
    .correction-summary-item span { color: #98a2b3; font-size: 8.5px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
    .correction-summary-item strong { margin-top: 5px; overflow: hidden; color: #101828; font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
    .correction-items { padding: 10px 18px 16px; }
    .correction-item { display: grid; grid-template-columns: minmax(0, 1fr) 110px 135px; gap: 14px; align-items: center; padding: 10px 0; border-bottom: 1px solid #eef0f3; }
    .correction-item:last-child { border-bottom: 0; }
    .correction-item strong,
    .correction-item span { display: block; }
    .correction-item strong { color: #101828; font-size: 10.5px; }
    .correction-item span { margin-top: 3px; color: #98a2b3; font-size: 9px; }
    .correction-item-value { color: #344054; font-size: 10px; }
    .correction-item-value span {
        margin: 0 0 3px;
        color: #98a2b3;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }
    .correction-item-value strong {
        margin: 0;
        color: #344054;
        font-size: 10px;
        font-weight: 700;
    }
    .correction-item-money { color: #155eef; font-size: 11px; font-weight: 700; text-align: right; }
    .correction-form { padding: 18px; }
    .correction-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
    .correction-field { min-width: 0; }
    .correction-field.is-full { grid-column: 1 / -1; }
    .correction-field label { display: block; margin: 0 0 6px; color: #344054; font-size: 10px; font-weight: 700; }
    .correction-field input,
    .correction-field select,
    .correction-field textarea {
        display: block; width: 100%; min-height: 40px; padding: 9px 11px; color: #101828; background: #fff;
        border: 1px solid #d0d5dd; border-radius: 7px; outline: 0; font: inherit; font-size: 11px;
    }
    .correction-field textarea { min-height: 84px; resize: vertical; }
    .correction-field input:focus,
    .correction-field select:focus,
    .correction-field textarea:focus { border-color: #84adff; box-shadow: 0 0 0 3px #eff4ff; }
    .correction-help { display: block; margin-top: 5px; color: #667085; font-size: 9px; line-height: 1.45; }
    .correction-error { display: block; margin-top: 5px; color: #b42318; font-size: 9px; font-weight: 600; }
    .correction-warning { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; padding: 11px 12px; color: #854a0e; background: #fffaeb; border: 1px solid #fedf89; border-radius: 8px; font-size: 10px; line-height: 1.5; }
    .correction-warning i { margin-top: 2px; color: #f79009; }
    .correction-form-actions { justify-content: flex-end; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #e4e7ec; }
    .correction-alert { align-items: flex-start; gap: 10px; padding: 11px 13px; color: #b42318; background: #fef3f2; border: 1px solid #fecdca; border-radius: 8px; font-size: 10.5px; }
    @media (max-width: 760px) {
        .correction-heading { align-items: flex-start; flex-direction: column; }
        .correction-heading-actions,
        .correction-heading-actions .correction-button { width: 100%; }
        .correction-summary { display: grid; grid-template-columns: 1fr 1fr; }
        .correction-summary-item + .correction-summary-item { padding-left: 0; border-left: 0; }
        .correction-grid { grid-template-columns: 1fr; }
        .correction-field.is-full { grid-column: auto; }
        .correction-item { grid-template-columns: minmax(0, 1fr); gap: 5px; }
        .correction-item-money { text-align: left; }
        .correction-form-actions { display: grid; grid-template-columns: 1fr; }
        .correction-button { width: 100% !important; }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="correction-page">
            @if(isset($errors) && $errors->any())
                <div class="correction-alert" role="alert">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <header class="correction-heading">
                <div>
                    <p class="correction-eyebrow">Administrasi pembayaran</p>
                    <h1 class="correction-title" data-page-title>Koreksi transaksi</h1>
                    <p class="correction-subtitle">Perbaiki siswa, metode, tanggal pembayaran, atau tanggal penerbitan kuitansi.</p>
                </div>
                <div class="correction-heading-actions">
                    <a class="correction-button" href="{{ route('riwayat.index') }}">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        <span>Kembali ke riwayat</span>
                    </a>
                </div>
            </header>

            <section class="correction-panel">
                <div class="correction-panel-head">
                    <div>
                        <h2>Transaksi saat ini</h2>
                        <p>Semua item di bawah akan dikoreksi sebagai satu transaksi.</p>
                    </div>
                    <span class="correction-reference"><i class="fas fa-receipt"></i>{{ $transactionReference }}</span>
                </div>

                <div class="correction-summary">
                    <div class="correction-summary-item">
                        <span>Siswa</span>
                        <strong>{{ $currentStudent?->nama ?? '-' }}</strong>
                    </div>
                    <div class="correction-summary-item">
                        <span>Metode</span>
                        <strong>{{ ucfirst((string) $firstPayment?->metode_bayar) }}</strong>
                    </div>
                    <div class="correction-summary-item">
                        <span>Tanggal bayar</span>
                        <strong>{{ $firstPayment?->tanggal_bayar?->translatedFormat('d M Y') ?? '-' }}</strong>
                    </div>
                    <div class="correction-summary-item">
                        <span>Tanggal kuitansi</span>
                        <strong>{{ ($firstPayment?->tanggal_kwitansi ?? $firstPayment?->tanggal_bayar)?->translatedFormat('d M Y') ?? '-' }}</strong>
                    </div>
                </div>

                <div class="correction-items">
                    @foreach($payments as $payment)
                        @php
                            $billPeriod = trim((string) ($payment->tagihan?->periode ?? $payment->periode ?? ''));
                            $isMonthlyBill = $payment->tagihan?->tipe === 'bulanan';
                            $periodLabel = $billPeriod !== '' ? $billPeriod : '-';

                            if ($isMonthlyBill && preg_match('/^\d{4}-\d{2}$/', $billPeriod)) {
                                $periodLabel = \Carbon\Carbon::createFromFormat('Y-m-d', $billPeriod . '-01')
                                    ->locale('id')
                                    ->translatedFormat('F Y');
                            }
                        @endphp
                        <div class="correction-item">
                            <div>
                                <strong>{{ $payment->tagihan?->nama_tagihan ?? 'Tagihan tidak tersedia' }}</strong>
                                <span>{{ $payment->tagihan?->tahunAjaran?->label ?? '-' }}</span>
                            </div>
                            <div class="correction-item-value">
                                <span>{{ $isMonthlyBill ? 'Bulan pembayaran' : 'Periode' }}</span>
                                <strong>{{ $periodLabel }}</strong>
                            </div>
                            <div class="correction-item-money">{{ $formatRupiah($payment->jumlah_bayar) }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="correction-panel">
                <div class="correction-panel-head">
                    <div>
                        <h2>Data yang benar</h2>
                        <p>Nilai uang yang diterima tetap. Potongan SPP dihitung ulang dari tanggal pembayaran.</p>
                    </div>
                </div>

                <form class="correction-form" method="POST" action="{{ route('pembayaran.koreksi.update', $anchor->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="correction-grid">
                        <div class="correction-field is-full">
                            <label for="correctionStudent">Siswa penerima pembayaran</label>
                            <select id="correctionStudent" name="siswa_id" required>
                                @foreach($studentGroups as $schoolName => $schoolStudents)
                                    <optgroup label="{{ $schoolName }}">
                                        @foreach($schoolStudents as $student)
                                            <option value="{{ $student->id }}" {{ (int) old('siswa_id', $currentStudent?->id) === (int) $student->id ? 'selected' : '' }}>
                                                {{ $student->nama }} · NIS {{ $student->nis }} · {{ $student->kelas?->kelas ?? 'Kelas belum diatur' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="correction-help">Jika siswa diganti, sistem mencari tagihan dengan jenis, tahun ajaran, dan periode yang sama pada siswa tujuan.</small>
                            @error('siswa_id')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="correction-field">
                            <label for="correctionPaymentDate">Tanggal pembayaran / transfer</label>
                            <input id="correctionPaymentDate" type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', $firstPayment?->tanggal_bayar?->format('Y-m-d')) }}" required>
                            <small class="correction-help">Gunakan tanggal uang diterima atau tanggal wali murid melakukan transfer, termasuk jika hari libur.</small>
                            @error('tanggal_bayar')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="correction-field">
                            <label for="correctionReceiptDate">Tanggal penerbitan kuitansi</label>
                            <input id="correctionReceiptDate" type="date" name="tanggal_kwitansi" value="{{ old('tanggal_kwitansi', ($firstPayment?->tanggal_kwitansi ?? $firstPayment?->tanggal_bayar)?->format('Y-m-d')) }}" required>
                            <small class="correction-help">Tanggal ini boleh berbeda dari tanggal pembayaran dan akan tampil pada kuitansi.</small>
                            @error('tanggal_kwitansi')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="correction-field">
                            <label for="correctionMethod">Metode pembayaran</label>
                            <select id="correctionMethod" name="metode_bayar" required>
                                <option value="tunai" {{ old('metode_bayar', $firstPayment?->metode_bayar) === 'tunai' ? 'selected' : '' }}>Tunai</option>
                                <option value="transfer" {{ old('metode_bayar', $firstPayment?->metode_bayar) === 'transfer' ? 'selected' : '' }}>Transfer bank</option>
                                <option value="kjc" {{ old('metode_bayar', $firstPayment?->metode_bayar) === 'kjc' ? 'selected' : '' }}>KJC</option>
                                <option value="tabungan" {{ old('metode_bayar', $firstPayment?->metode_bayar) === 'tabungan' ? 'selected' : '' }}>Potongan tabungan</option>
                            </select>
                            @error('metode_bayar')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>

                        <div class="correction-field">
                            <label for="correctionNote">Keterangan transaksi <span style="color:#98a2b3;font-weight:500">(opsional)</span></label>
                            <input id="correctionNote" type="text" name="keterangan" value="{{ old('keterangan', $firstPayment?->keterangan) }}" placeholder="Contoh: Transfer BCA atas nama wali murid">
                            @error('keterangan')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>

                        @foreach($payments as $payment)
                        <div class="correction-field">
                            <label for="amount-{{ $payment->id }}">Nominal {{ app(\App\Services\BillDisplayName::class)->receipt($payment->tagihan) }}</label>
                            <input type="number" id="amount-{{ $payment->id }}" name="amounts[{{ $payment->id }}]" min="0.01" step="0.01" value="{{ old('amounts.'.$payment->id, $payment->jumlah_bayar) }}" required>
                            @error('amounts.'.$payment->id)<small class="correction-error">{{ $message }}</small>@enderror
                        </div>
                        @endforeach
                        <div class="correction-field is-full">
                            <label for="correctionReason">Alasan koreksi</label>
                            <textarea id="correctionReason" name="alasan_koreksi" required placeholder="Contoh: Metode pembayaran seharusnya transfer bank, bukan tunai.">{{ old('alasan_koreksi') }}</textarea>
                            <small class="correction-help">Wajib diisi. Alasan dan data sebelum/sesudah disimpan di log aktivitas.</small>
                            @error('alasan_koreksi')<small class="correction-error">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <div class="correction-warning">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>Koreksi tidak menghapus transaksi. Diskon pelunasan SPP {{ $sppDiscountConfig['active'] ? 'sebesar Rp' . number_format($sppDiscountConfig['amount'], 0, ',', '.') : 'sedang nonaktif' }} dan akan dihitung ulang mengikuti periode yang berlaku, lalu status tagihan dan kuitansi diperbarui.</span>
                    </div>

                    <div class="correction-form-actions">
                        <a class="correction-button is-danger" href="{{ route('riwayat.index') }}">
                            <i class="fas fa-xmark"></i><span>Batal</span>
                        </a>
                        <button class="correction-button is-primary" type="submit">
                            <i class="fas fa-floppy-disk"></i><span>Simpan koreksi</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
