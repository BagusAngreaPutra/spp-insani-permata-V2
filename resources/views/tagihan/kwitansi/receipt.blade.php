@extends('layouts.app')
@include('layouts.sidebar')

@section('title', $receipt['document_label'] ?? 'Kwitansi Pembayaran')

@section('content')
@php
    $safeNumber = trim((string) preg_replace(
        '/[^A-Za-z0-9._-]+/',
        '-',
        str_replace(['/', '\\'], '-', (string) ($receipt['number'] ?? 'pembayaran'))
    ), '-');
    $pdfFilename = 'kwitansi-' . ($safeNumber ?: 'pembayaran') . '.pdf';
    $pdfUrl = (string) ($receipt['pdf_url'] ?? '#');
    $pdfPreviewUrl = $pdfUrl . (str_contains($pdfUrl, '?') ? '&' : '?') . 'preview=1';
    $clientPdfFallback = (bool) ($clientPdfFallback ?? false);
    $clientPrintFallback = (bool) ($clientPrintFallback ?? false);
@endphp

@push('page-styles')
<style>
    .receipt-page,
    .receipt-page * { box-sizing: border-box; }
    .receipt-page { display: grid; justify-items: center; gap: 18px; min-width: 0; width: 100%; }
    .receipt-screen-heading { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .receipt-eyebrow { margin: 0 0 4px; color: #98a2b3; font-size: 10px; font-weight: 650; }
    .receipt-title { margin: 0; color: #101828; font-size: clamp(25px, 2.6vw, 32px); font-weight: 750; letter-spacing: -.04em; }
    .receipt-subtitle { margin: 7px 0 0; color: #667085; font-size: 12px; }
    .receipt-actions { display: flex; align-items: center; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
    .receipt-button {
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
        font: inherit !important;
        font-size: 11px !important;
        font-weight: 650 !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }
    .receipt-button.is-primary { color: #fff !important; background: #1d6b4c !important; border-color: #1d6b4c !important; }
    .receipt-button:hover { filter: brightness(.97); }
    .receipt-paper {
        justify-self: center;
        width: min(100%, 148mm);
        min-height: 210mm;
        margin-right: auto !important;
        margin-left: auto !important;
        padding: 6mm;
        color: #1f2937;
        background: #fff;
        border: 1px solid #d0d5dd;
        box-shadow: 0 12px 32px rgba(16, 24, 40, .08);
    }

    @media (max-width: 700px) {
        .receipt-screen-heading { align-items: stretch; flex-direction: column; }
        .receipt-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .receipt-button { width: 100% !important; }
        .receipt-paper { width: 100%; min-height: 0; padding: 16px 12px; }
    }

    @media print {
        @page { size: A5 portrait; margin: 6mm; }
        html, body { width: 148mm !important; min-width: 148mm !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
        body * { visibility: hidden !important; }
        [data-receipt-paper], [data-receipt-paper] * { visibility: visible !important; }
        [data-receipt-paper] {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 136mm !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

{{-- Diletakkan setelah stylesheet global agar ukuran kertas tidak kembali menjadi A4. --}}
<style>
    @media print {
        @page { size: A5 portrait; margin: 6mm; }
        [data-receipt-paper] {
            right: auto !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document,
        body [data-receipt-paper] [data-receipt-document].receipt-document * {
            font-family: Arial, Helvetica, sans-serif !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document {
            font-size: 8.5px !important;
            line-height: 1.35 !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document table th,
        body [data-receipt-paper] [data-receipt-document].receipt-document table td {
            font-size: 8.5px !important;
            line-height: 1.35 !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-document-header td { padding-bottom: 2mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-meta { margin-top: 1.5mm !important; margin-bottom: 1.5mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-meta td { padding-top: 1.6mm !important; padding-bottom: 1.6mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-recipient { margin-bottom: 1.5mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-recipient td { padding-bottom: 1.8mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-section-heading { margin-bottom: 1mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-brand-cell { width: 67% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-id-cell { width: 33% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-brand-copy strong { font-size: 9.5px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-brand-copy span { font-size: 8px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-label { font-size: 7.5px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-number { font-size: 9.5px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-state { font-size: 8px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-meta td { width: 25% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-recipient td { width: 50% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-value { font-size: 9px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-recipient small { font-size: 8.5px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-section-heading strong,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-section-heading span { font-size: 9px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-items th { padding-top: 1.6mm !important; padding-bottom: 1.6mm !important; font-size: 8px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-items td { padding-top: 1.8mm !important; padding-bottom: 1.8mm !important; font-size: 9px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-items {
            table-layout: fixed !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-items td strong {
            font-size: 9px !important;
            line-height: 1.35 !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-col-number { width: 7% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-col-description { width: 45% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-col-money { width: 16% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-item-meta { font-size: 8.5px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-spelled { width: 51% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-summary { width: 49% !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-spelled strong,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-summary-row,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-note,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-validity strong,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-validity span,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-signature {
            font-size: 9px !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-summary-row.is-total,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-summary-row.is-total strong,
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-signature-line {
            font-size: 9.5px !important;
        }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-footer { font-size: 8px !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-totals { margin-top: 1.5mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-note { margin-top: 1.5mm !important; padding-top: 1.2mm !important; padding-bottom: 1.2mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-signatures { margin-top: 1.8mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-signatures td { padding-top: 1.8mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-signature-line { margin-top: 6mm !important; }
        body [data-receipt-paper] [data-receipt-document].receipt-document .receipt-footer { margin-top: 1.5mm !important; padding-top: 1mm !important; }
    }
</style>

<div class="main-content">
    @include('layouts.header')
    <div class="content-area">
        <div class="receipt-page">
            <header class="receipt-screen-heading">
                <div>
                    <p class="receipt-eyebrow"><i class="fas fa-receipt" aria-hidden="true"></i> Dokumen transaksi</p>
                    <h1 class="receipt-title">{{ $receipt['document_label'] ?? 'Kwitansi pembayaran' }}</h1>
                    <p class="receipt-subtitle">Pratinjau A5 portrait. Tampilan ini sama dengan dokumen cetak dan PDF.</p>
                </div>
                <div class="receipt-actions transaction-actions">
                    @if(!empty($receipt['edit_url']) && Auth::guard('web')->user()?->hasPermission($receipt['manage_permission']))
                        <a class="receipt-button transaction-action" href="{{ $receipt['edit_url'] }}"><i class="fas fa-pen"></i><span>Koreksi transaksi</span></a>
                    @endif
                    @if(!empty($receipt['back_url']))
                        <a class="receipt-button transaction-action" href="{{ $receipt['back_url'] }}"><i class="fas fa-arrow-left"></i><span>Kembali</span></a>
                    @endif
                    <button class="receipt-button transaction-action" type="button" onclick="window.print()"><i class="fas fa-print"></i><span>Cetak</span></button>
                    @if($pdfUrl !== '#')
                        <a class="receipt-button" href="{{ $pdfPreviewUrl }}" target="_blank"><i class="fas fa-eye"></i><span>Lihat PDF</span></a>
                        <a class="receipt-button transaction-action" href="{{ $pdfUrl }}"><i class="fas fa-file-arrow-down"></i><span>Unduh PDF</span></a>
                    @endif
                    @if(!empty($receipt['cancel_url']) && Auth::guard('web')->user()?->hasPermission($receipt['manage_permission']))
                        <form method="POST" action="{{ $receipt['cancel_url'] }}" data-confirm-reason data-confirm-title="Batalkan / hapus transaksi" data-confirm-message="Seluruh transaksi pada kwitansi ini dibatalkan. Saldo tagihan atau stok serta kas masuk akan dihitung ulang." data-confirm-label="Batalkan transaksi" data-confirm-tone="danger">
                            @csrf @method('DELETE')
                            <input type="hidden" name="alasan_pembatalan" value="">
                            <button type="submit" class="transaction-action is-danger btn-danger"><i class="fas fa-trash-can" aria-hidden="true"></i><span>Batalkan / Hapus transaksi</span></button>
                        </form>
                    @endif
                </div>
            </header>

            <p class="receipt-preview-help">Geser pratinjau ke samping untuk melihat seluruh kwitansi.</p>
            <div class="receipt-preview" tabindex="0" role="region" aria-label="Pratinjau kwitansi, dapat digeser ke samping">
                <div class="receipt-paper" data-receipt-paper>
                    @include('tagihan.kwitansi._document', ['receipt' => $receipt])
                </div>
            </div>
        </div>
    </div>
</div>

@if($clientPdfFallback || $clientPrintFallback)
<script src="{{ asset('js/html2pdf.bundle.min.js') }}?v=0.10.1"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const receipt = document.querySelector('[data-receipt-document]');
        const shouldPrint = @json($clientPrintFallback);

        if (!receipt) return;
        if (shouldPrint) {
            window.setTimeout(() => window.print(), 150);
            return;
        }
        if (typeof window.html2pdf !== 'function') {
            window.alert('Generator PDF belum tersedia. Silakan gunakan tombol Cetak.');
            return;
        }
        if (document.fonts?.ready) await document.fonts.ready;

        await window.html2pdf()
            .set({
                margin: [10, 6, 6, 6],
                filename: @json($pdfFilename),
                image: { type: 'jpeg', quality: 1 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                },
                jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait', compress: true },
                pagebreak: { mode: ['css', 'legacy'] },
            })
            .from(receipt)
            .save();
    });
</script>
@endif
@endsection
