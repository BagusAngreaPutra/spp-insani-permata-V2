@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Kwitansi Pembayaran')

@section('content')
@php
    $student = $receipt['student'];
    $school = $student?->sekolah;
    $items = collect($receipt['items']);
    $paidTotal = (float) $receipt['total_paid'];
    $discountTotal = (float) $receipt['total_discount'];
    $transactionTotal = $paidTotal + $discountTotal;
    $paymentDate = $receipt['payment_date'] ?? $receipt['date'] ?? null;
    $receiptDate = $receipt['receipt_date'] ?? $paymentDate;
    $paidAt = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : now();
    $receiptAt = $receiptDate ? \Carbon\Carbon::parse($receiptDate) : $paidAt;
    $isGrouped = $items->count() > 1;
    $formatRupiah = static fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $className = trim((string) ($student?->kelas?->nama_kelas ?? ''));
    $classLabel = !$student?->kelas
        ? 'Kelas belum diatur'
        : (in_array($className, ['', '-', '–'], true)
            ? $student->kelas->label_tingkat
            : $student->kelas->label_tingkat . ' · ' . $className);
    $rawNote = trim((string) ($receipt['note'] ?? ''));
    $showNote = $rawNote !== ''
        && !preg_match('/^Pembayaran multi-tagihan(?:\s+\(Diskon:.*\))?$/i', $rawNote);
    $spelledAmount = ucfirst(trim(terbilang((int) $paidTotal))) . ' rupiah';
    $pdfFilename = 'kwitansi-' . trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', str_replace(['/', '\\'], '-', (string) $receipt['number'])), '-') . '.pdf';
    $autoDownloadPdf = request()->boolean('download');
@endphp

@push('page-styles')
<style>
    .receipt-page,
    .receipt-page * {
        box-sizing: border-box;
    }

    .receipt-page {
        display: grid;
        min-width: 0;
        gap: 18px;
    }

    .receipt-screen-heading,
    .receipt-actions,
    .receipt-brand,
    .receipt-paid-badge,
    .receipt-section-heading,
    .receipt-summary-row,
    .receipt-validity {
        display: flex;
        align-items: center;
    }

    .receipt-screen-heading {
        justify-content: space-between;
        min-width: 0;
        gap: 22px;
    }

    .receipt-eyebrow {
        margin: 0 0 3px;
        color: #888;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .receipt-title {
        margin: 0;
        color: #181818;
        font-size: clamp(25px, 2.6vw, 32px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.16;
    }

    .receipt-subtitle {
        margin: 7px 0 0;
        color: #666;
        font-size: 12px;
    }

    .receipt-actions {
        flex: 0 0 auto;
        justify-content: flex-end;
        gap: 8px;
    }

    .receipt-button {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        min-height: 38px !important;
        margin: 0 !important;
        padding: 0 13px !important;
        color: #333 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 3px !important;
        box-shadow: none !important;
        font-family: inherit !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-decoration: none !important;
        cursor: pointer !important;
    }

    .receipt-button.is-primary {
        color: #fff !important;
        background: #181818 !important;
        border-color: #181818 !important;
    }

    .receipt-button:hover {
        color: #181818 !important;
        background: #fff !important;
    }

    .receipt-button.is-primary:hover {
        color: #fff !important;
        background: #333 !important;
        border-color: #333 !important;
    }

    .receipt-paper {
        width: min(100%, 760px);
        min-width: 0;
        margin: 0 auto;
        padding: 32px 36px 24px;
        overflow: hidden;
        color: #181818;
        background: #fff;
        border: 1px solid #d7d7d7;
        border-radius: 0;
        box-shadow: none;
    }

    .receipt-document-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(210px, auto);
        align-items: start;
        gap: 24px;
        padding-bottom: 22px;
        border-bottom: 2px solid #181818;
    }

    .receipt-brand {
        align-items: flex-start;
        min-width: 0;
        gap: 12px;
    }

    .receipt-brand img {
        flex: 0 0 48px;
        width: 48px;
        height: 48px;
        object-fit: contain;
        filter: grayscale(1);
        opacity: .8;
    }

    .receipt-brand-copy {
        min-width: 0;
    }

    .receipt-brand-copy strong,
    .receipt-brand-copy span {
        display: block;
    }

    .receipt-brand-copy strong {
        color: #181818;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: -.02em;
        line-height: 1.25;
    }

    .receipt-brand-copy span {
        max-width: 430px;
        margin-top: 3px;
        color: #666;
        font-size: 10px;
        line-height: 1.45;
    }

    .receipt-document-id {
        min-width: 0;
        padding: 0;
        text-align: right;
        background: transparent;
        border: 0;
        border-radius: 0;
    }

    .receipt-document-id > span,
    .receipt-document-id > strong {
        display: block;
    }

    .receipt-document-label {
        color: #666;
        font-size: 8.5px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .receipt-document-id > strong {
        margin-top: 5px;
        overflow-wrap: anywhere;
        color: #181818;
        font-size: 16px;
        font-weight: 750;
        letter-spacing: -.02em;
        line-height: 1.25;
    }

    .receipt-document-id > .receipt-paid-badge {
        display: flex;
    }

    .receipt-paid-badge {
        justify-content: flex-end;
        margin-top: 7px;
        color: #555;
        font-size: 8.5px;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .receipt-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0;
        margin: 18px 0;
        padding: 12px 0;
        border-top: 1px solid #d7d7d7;
        border-bottom: 1px solid #d7d7d7;
    }

    .receipt-meta.is-with-receipt-date {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .receipt-meta-item {
        min-width: 0;
        padding: 0 16px;
        background: transparent;
        border: 0;
        border-radius: 0;
    }

    .receipt-meta-item:first-child {
        padding-left: 0;
    }

    .receipt-meta-item + .receipt-meta-item {
        border-left: 1px solid #e2e2e2;
    }

    .receipt-meta-item span,
    .receipt-meta-item strong {
        display: block;
    }

    .receipt-meta-item span {
        color: #777;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .receipt-meta-item strong {
        margin-top: 4px;
        overflow: hidden;
        color: #333;
        font-size: 11px;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .receipt-recipient {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
        gap: 0;
        margin-bottom: 20px;
        padding: 0 0 16px;
        background: transparent;
        border: 0;
        border-bottom: 1px solid #d7d7d7;
        border-radius: 0;
    }

    .receipt-recipient-block {
        min-width: 0;
    }

    .receipt-recipient-block + .receipt-recipient-block {
        margin-left: 20px;
        padding-left: 20px;
        border-left: 1px solid #e4e7ec;
    }

    .receipt-recipient-block > span,
    .receipt-recipient-block > strong,
    .receipt-recipient-block > small {
        display: block;
    }

    .receipt-recipient-block > span {
        color: #777;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .receipt-recipient-block > strong {
        margin-top: 5px;
        overflow-wrap: anywhere;
        color: #181818;
        font-size: 12px;
        font-weight: 700;
    }

    .receipt-recipient-block > small {
        margin-top: 3px;
        color: #666;
        font-size: 9.5px;
        line-height: 1.45;
    }

    .receipt-section-heading {
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .receipt-section-heading strong {
        color: #181818;
        font-size: 11.5px;
        font-weight: 700;
    }

    .receipt-section-heading span {
        display: inline;
        min-height: 0;
        padding: 0;
        color: #4a4a4a;
        background: transparent;
        border: 0;
        border-radius: 0;
        font-size: 8.5px;
        font-weight: 650;
    }

    .receipt-table-wrap {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow-x: auto;
        border-top: 1px solid #999;
        border-bottom: 1px solid #999;
        border-radius: 0;
    }

    .receipt-table {
        width: 100% !important;
        min-width: 620px !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        background: #fff !important;
    }

    .receipt-table thead,
    .receipt-table tr {
        background: #fff !important;
    }

    .receipt-table th {
        height: 36px !important;
        padding: 8px 10px !important;
        color: #555 !important;
        background: #fff !important;
        border-bottom: 1px solid #dfe3e8 !important;
        font-size: 8.5px !important;
        font-weight: 700 !important;
        text-align: left !important;
        text-transform: uppercase;
    }

    .receipt-table td {
        height: 44px !important;
        padding: 9px 10px !important;
        overflow-wrap: anywhere;
        color: #333 !important;
        background: #fff !important;
        border-bottom: 1px solid #eef0f3 !important;
        font-size: 10px !important;
        line-height: 1.35;
        vertical-align: middle !important;
    }

    .receipt-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .receipt-table-number {
        width: 42px;
        color: #777 !important;
        text-align: center !important;
    }

    .receipt-table-description {
        width: auto;
    }

    .receipt-table-description strong {
        color: #333;
        font-weight: 600;
    }

    .receipt-table-amount {
        width: 126px;
    }

    .receipt-money {
        font-variant-numeric: tabular-nums;
        text-align: right !important;
        white-space: nowrap;
    }

    .receipt-totals-area {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
        align-items: start;
        gap: 18px;
        margin-top: 17px;
    }

    .receipt-spelled {
        min-width: 0;
        padding: 10px 0;
        color: #4a4a4a;
        background: transparent;
        border: 0;
        border-radius: 0;
    }

    .receipt-spelled span,
    .receipt-spelled strong {
        display: block;
    }

    .receipt-spelled span {
        color: #555;
        font-size: 8px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .receipt-spelled strong {
        margin-top: 5px;
        color: #333;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.55;
    }

    .receipt-summary {
        width: 100%;
        padding: 0 0 0 18px;
        background: transparent;
        border: 0;
        border-left: 1px solid #d7d7d7;
        border-radius: 0;
    }

    .receipt-summary-row {
        justify-content: space-between;
        gap: 18px;
        min-height: 27px;
        color: #666;
        font-size: 9.5px;
    }

    .receipt-summary-row strong {
        color: #333;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .receipt-summary-row.is-total {
        margin-top: 6px;
        padding-top: 10px;
        color: #181818;
        border-top: 1px solid #d0d5dd;
        font-size: 10.5px;
        font-weight: 700;
    }

    .receipt-summary-row.is-total strong {
        color: #181818;
        font-size: 14px;
        font-weight: 750;
    }

    .receipt-note {
        display: block;
        margin-top: 14px;
        padding: 10px 0;
        color: #4a4a4a;
        background: transparent;
        border: 0;
        border-top: 1px solid #d7d7d7;
        border-bottom: 1px solid #d7d7d7;
        border-radius: 0;
        font-size: 9.5px;
        line-height: 1.5;
    }

    .receipt-note strong,
    .receipt-note span {
        display: block;
    }

    .receipt-note strong {
        color: #333;
        font-size: 9px;
        font-weight: 700;
    }

    .receipt-note span {
        margin-top: 2px;
    }

    .receipt-signature-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 230px;
        align-items: end;
        gap: 30px;
        margin-top: 28px;
        padding-top: 22px;
        border-top: 1px solid #e4e7ec;
    }

    .receipt-validity {
        align-items: flex-start;
        min-width: 0;
        max-width: 390px;
    }

    .receipt-validity strong,
    .receipt-validity span {
        display: block;
    }

    .receipt-validity strong {
        color: #333;
        font-size: 9.5px;
        font-weight: 700;
    }

    .receipt-validity span {
        margin-top: 3px;
        color: #777;
        font-size: 8.5px;
        line-height: 1.5;
    }

    .receipt-signature {
        width: 100%;
        color: #4a4a4a;
        font-size: 9.5px;
        text-align: center;
    }

    .receipt-signature-date {
        display: block;
    }

    .receipt-signature-line {
        margin-top: 48px;
        padding-top: 7px;
        color: #181818;
        border-top: 1px solid #666;
        font-weight: 650;
    }

    .receipt-document-footer {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        margin-top: 21px;
        padding-top: 10px;
        color: #777;
        border-top: 1px dashed #d0d5dd;
        font-size: 7.5px;
        line-height: 1.4;
    }

    .receipt-document-footer span:last-child {
        text-align: right;
        overflow-wrap: anywhere;
    }

    @media (max-width: 760px) {
        .receipt-screen-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .receipt-screen-heading > div:first-child {
            min-width: 0;
            max-width: 100%;
        }

        .receipt-subtitle {
            max-width: 100%;
        }

        .receipt-actions {
            display: grid;
            grid-template-columns: 1fr;
            width: 100%;
        }

        .receipt-button {
            width: 100% !important;
        }

        .receipt-paper {
            width: 100%;
            max-width: 100%;
            padding: 19px 16px 16px;
            border-radius: 0;
        }

        .receipt-document-header {
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
            padding-bottom: 17px;
        }

        .receipt-brand {
            width: 100%;
        }

        .receipt-brand img {
            flex-basis: 42px;
            width: 42px;
            height: 42px;
        }

        .receipt-brand-copy strong {
            font-size: 13px;
        }

        .receipt-document-id {
            width: 100%;
            text-align: left;
        }

        .receipt-paid-badge {
            justify-content: flex-start;
        }

        .receipt-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .receipt-meta.is-with-receipt-date {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .receipt-meta.is-with-receipt-date .receipt-meta-item:last-child {
            grid-column: auto;
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
            border-left: 1px solid #e2e2e2;
        }

        .receipt-meta-item:last-child {
            grid-column: 1 / -1;
            margin-top: 12px;
            padding-top: 12px;
            padding-left: 0;
            border-top: 1px solid #e2e2e2;
            border-left: 0;
        }

        .receipt-meta-item strong {
            white-space: normal;
        }

        .receipt-recipient {
            grid-template-columns: minmax(0, 1fr);
            gap: 13px;
        }

        .receipt-recipient-block + .receipt-recipient-block {
            margin-left: 0;
            padding-top: 13px;
            padding-left: 0;
            border-top: 1px solid #e4e7ec;
            border-left: 0;
        }

        .receipt-table-wrap {
            overflow: visible;
            border: 0;
        }

        body:has(.app-sidebar) .receipt-page .receipt-table {
            display: block !important;
            min-width: 0 !important;
            table-layout: auto !important;
            background: transparent !important;
        }

        .receipt-table thead {
            display: none !important;
        }

        .receipt-table tbody,
        .receipt-table tr,
        .receipt-table td {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .receipt-table tr {
            margin-bottom: 0;
            overflow: visible;
            background: transparent;
            border: 0;
            border-bottom: 1px solid #cfcfcf;
            border-radius: 0;
        }

        .receipt-table tr:last-child {
            margin-bottom: 0;
            border-bottom: 0;
        }

        .receipt-table td {
            display: grid !important;
            grid-template-columns: 102px minmax(0, 1fr) !important;
            align-items: center !important;
            min-height: 34px !important;
            height: auto !important;
            padding: 7px 9px !important;
            text-align: right !important;
            white-space: normal !important;
            border-bottom: 1px solid #eef0f3 !important;
        }

        .receipt-table td::before {
            color: #777;
            font-size: 8px;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            content: attr(data-label);
        }

        .receipt-table td:last-child {
            border-bottom: 0 !important;
        }

        .receipt-table-number {
            display: none !important;
        }

        .receipt-table-description {
            grid-template-columns: 1fr !important;
            padding: 10px !important;
            text-align: left !important;
            background: transparent !important;
        }

        .receipt-table-description::before {
            margin-bottom: 4px;
        }

        .receipt-totals-area {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .receipt-summary {
            grid-row: 1;
            padding: 0 0 12px;
            border-bottom: 1px solid #d7d7d7;
            border-left: 0;
        }

        .receipt-signature-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
        }

        .receipt-signature {
            width: min(100%, 230px);
            margin-left: auto;
        }

        .receipt-document-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .receipt-document-footer span:last-child {
            text-align: left;
        }
    }

    @media (max-width: 360px) {
        .receipt-meta {
            grid-template-columns: minmax(0, 1fr);
        }

        .receipt-meta-item:last-child {
            grid-column: auto;
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .receipt-meta-item,
        .receipt-meta-item:first-child,
        .receipt-meta-item:last-child {
            padding-right: 0;
            padding-left: 0;
            border-left: 0;
        }

        .receipt-meta-item + .receipt-meta-item {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e2e2;
        }

        .receipt-table td {
            grid-template-columns: 88px minmax(0, 1fr) !important;
        }
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 11mm;
        }

        html,
        body {
            width: 100% !important;
            color: #000 !important;
            background: #fff !important;
        }

        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .no-print,
        .app-sidebar,
        .app-topbar,
        .sidebar-overlay-bg {
            display: none !important;
        }

        body:has(.app-sidebar) .main-content {
            width: 100% !important;
            max-width: 100% !important;
            min-height: auto !important;
            margin: 0 !important;
        }

        body:has(.app-sidebar) .content-area {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
        }

        .receipt-page {
            display: block !important;
            width: 100% !important;
        }

        .receipt-paper {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .receipt-document-header,
        .receipt-meta,
        .receipt-recipient,
        .receipt-totals-area,
        .receipt-note,
        .receipt-signature-grid,
        .receipt-document-footer {
            break-inside: avoid;
        }

        .receipt-document-id,
        .receipt-meta-item,
        .receipt-spelled,
        .receipt-summary {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .receipt-table-wrap {
            overflow: visible !important;
        }

        .receipt-table {
            display: table !important;
            width: 100% !important;
            min-width: 0 !important;
            table-layout: fixed !important;
        }

        .receipt-table thead {
            display: table-header-group !important;
        }

        .receipt-table tbody {
            display: table-row-group !important;
        }

        .receipt-table tr {
            display: table-row !important;
            break-inside: avoid;
            border: 0 !important;
        }

        .receipt-table th,
        .receipt-table td {
            display: table-cell !important;
            max-width: none !important;
            text-align: left !important;
        }

        .receipt-table td::before {
            display: none !important;
        }

        .receipt-table .receipt-table-number {
            display: table-cell !important;
            width: 42px !important;
            text-align: center !important;
        }

        .receipt-table .receipt-table-description {
            width: auto !important;
            background: #fff !important;
        }

        .receipt-table .receipt-table-amount,
        .receipt-table .receipt-money {
            width: 126px !important;
            text-align: right !important;
            white-space: nowrap !important;
        }

        .receipt-document-footer {
            margin-top: 16px;
        }

        /* Aturan akhir khusus kuitansi pembayaran: A4 potret, bukan landscape. */
        @page {
            size: 210mm 297mm;
            margin: 12mm;
        }

        html,
        body {
            width: auto !important;
            min-width: 0 !important;
            max-width: none !important;
        }

        body:has(.app-sidebar) .receipt-page,
        body:has(.app-sidebar) .receipt-paper.pi-receipt-document {
            width: 186mm !important;
            min-width: 0 !important;
            max-width: 186mm !important;
            margin: 0 auto !important;
            overflow: visible !important;
        }

        body:has(.app-sidebar) .receipt-paper.pi-receipt-document .receipt-table {
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }
    }
</style>
@endpush

{{--
    Harus berada di body (setelah permata-system.css) agar aturan laporan global
    A4 landscape tidak menimpa orientasi kuitansi pembayaran.
--}}
<style id="receipt-print-page-override">
    body:has(.app-sidebar) .receipt-paper.pi-receipt-document.is-pdf-export {
        width: 186mm !important;
        min-width: 186mm !important;
        max-width: 186mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    body:has(.app-sidebar) .receipt-paper.pi-receipt-document.is-pdf-export .receipt-table-wrap {
        overflow: visible !important;
    }

    body:has(.app-sidebar) .receipt-paper.pi-receipt-document.is-pdf-export .receipt-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        table-layout: fixed !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
    }
</style>

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="receipt-page">
            <header class="receipt-screen-heading no-print">
                <div>
                    <p class="receipt-eyebrow">Pembayaran selesai</p>
                    <h1 class="receipt-title" data-page-title>Kwitansi pembayaran</h1>
                    <p class="receipt-subtitle">Periksa rincian transaksi sebelum mencetak atau menyerahkan kwitansi.</p>
                </div>
                <div class="receipt-actions">
                    <a class="receipt-button" href="{{ route('tagihan.proses.siswa', $student->id) }}">
                        <span>Kembali ke siswa</span>
                    </a>
                    <button
                        class="receipt-button"
                        type="button"
                        data-download-receipt-pdf
                        data-filename="{{ $pdfFilename }}"
                        data-fallback-url="{{ $receipt['pdf_url'] }}"
                    >
                        <span>Unduh PDF</span>
                    </button>
                    <button class="receipt-button is-primary" type="button" onclick="window.print()">
                        <span>Cetak kwitansi</span>
                    </button>
                </div>
            </header>

            <article class="receipt-paper pi-print-document pi-receipt-document" data-receipt-document>
                <header class="receipt-document-header">
                    <div class="receipt-brand">
                        <img src="{{ asset('images/logo.jpg') }}" alt="Logo Permata Insani">
                        <div class="receipt-brand-copy">
                            <strong>Yayasan Kemilau Permata Insani</strong>
                            <span>{{ $school?->nama_sekolah ?? 'Permata Insani Islamic School' }}</span>
                            <span>{{ $school?->alamat ?? 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi' }}</span>
                        </div>
                    </div>

                    <div class="receipt-document-id">
                        <span class="receipt-document-label">
                            {{ $isGrouped ? 'Kwitansi gabungan' : 'Kwitansi pembayaran' }}
                        </span>
                        <strong>{{ $receipt['number'] }}</strong>
                        <span class="receipt-paid-badge">
                            Pembayaran diterima
                        </span>
                    </div>
                </header>

                <section class="receipt-meta is-with-receipt-date" aria-label="Informasi transaksi">
                    <div class="receipt-meta-item">
                        <span>Tanggal pembayaran</span>
                        <strong>{{ $paidAt->translatedFormat('d F Y') }}</strong>
                    </div>
                    <div class="receipt-meta-item">
                        <span>Tanggal kuitansi</span>
                        <strong>{{ $receiptAt->translatedFormat('d F Y') }}</strong>
                    </div>
                    <div class="receipt-meta-item">
                        <span>Metode pembayaran</span>
                        <strong>{{ $receipt['method'] }}</strong>
                    </div>
                    <div class="receipt-meta-item">
                        <span>Jumlah item</span>
                        <strong>{{ $items->count() }} tagihan</strong>
                    </div>
                </section>

                <section class="receipt-recipient" aria-label="Identitas penerima">
                    <div class="receipt-recipient-block">
                        <span>Diterima dari</span>
                        <strong>{{ $student?->nama ?? 'Nama siswa tidak tersedia' }}</strong>
                        <small>NIS {{ $student?->nis ?? '-' }} · {{ $classLabel }}</small>
                    </div>
                    <div class="receipt-recipient-block">
                        <span>Unit sekolah</span>
                        <strong>{{ $school?->nama_sekolah ?? 'Sekolah belum diatur' }}</strong>
                        <small>{{ $school?->kode_sekolah ? 'Kode sekolah ' . $school->kode_sekolah : 'Kode sekolah belum diatur' }}</small>
                    </div>
                </section>

                <section aria-labelledby="receiptItemsTitle">
                    <div class="receipt-section-heading">
                        <strong id="receiptItemsTitle">Rincian transaksi</strong>
                        <span>{{ $items->count() }} item</span>
                    </div>

                    <div class="receipt-table-wrap">
                        <table class="receipt-table" data-sort-disabled="true" data-sortable="false">
                            <thead>
                                <tr>
                                    <th class="receipt-table-number">No.</th>
                                    <th class="receipt-table-description">Rincian pembayaran</th>
                                    <th class="receipt-table-amount receipt-money">Nilai transaksi</th>
                                    <th class="receipt-table-amount receipt-money">Potongan</th>
                                    <th class="receipt-table-amount receipt-money">Diterima</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td class="receipt-table-number" data-label="No.">{{ $loop->iteration }}</td>
                                        <td class="receipt-table-description" data-label="Pembayaran">
                                            <strong>{{ $item['name'] }}</strong>
                                        </td>
                                        <td class="receipt-table-amount receipt-money" data-label="Nilai transaksi">
                                            {{ $formatRupiah($item['amount'] + $item['discount']) }}
                                        </td>
                                        <td class="receipt-table-amount receipt-money" data-label="Potongan">
                                            {{ $item['discount'] > 0 ? '- ' . $formatRupiah($item['discount']) : '—' }}
                                        </td>
                                        <td class="receipt-table-amount receipt-money" data-label="Diterima">
                                            <strong>{{ $formatRupiah($item['amount']) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="receipt-totals-area" aria-label="Total pembayaran">
                    <div class="receipt-spelled">
                        <span>Terbilang</span>
                        <strong>{{ $spelledAmount }}</strong>
                    </div>

                    <div class="receipt-summary">
                        <div class="receipt-summary-row">
                            <span>Nilai transaksi</span>
                            <strong>{{ $formatRupiah($transactionTotal) }}</strong>
                        </div>
                        @if($discountTotal > 0)
                            <div class="receipt-summary-row">
                                <span>Total potongan</span>
                                <strong>- {{ $formatRupiah($discountTotal) }}</strong>
                            </div>
                        @endif
                        <div class="receipt-summary-row is-total">
                            <span>Jumlah diterima</span>
                            <strong>{{ $formatRupiah($paidTotal) }}</strong>
                        </div>
                    </div>
                </section>

                @if($showNote)
                    <div class="receipt-note">
                        <div>
                            <strong>Keterangan</strong>
                            <span>{{ $rawNote }}</span>
                        </div>
                    </div>
                @endif

                <footer class="receipt-signature-grid">
                    <div class="receipt-validity">
                        <div>
                            <strong>Bukti pembayaran resmi</strong>
                            <span>Kwitansi ini dibuat oleh Sistem Pembayaran Permata Insani dan sah tanpa stempel tambahan.</span>
                        </div>
                    </div>

                    <div class="receipt-signature">
                        <span class="receipt-signature-date">Jambi, {{ $receiptAt->translatedFormat('d F Y') }}</span>
                        <div class="receipt-signature-line">Petugas administrasi</div>
                    </div>
                </footer>

                <div class="receipt-document-footer">
                    <span>Dokumen dicetak melalui Sistem Pembayaran Permata Insani.</span>
                    <span>No. {{ $receipt['number'] }}</span>
                </div>
            </article>
        </div>
    </div>
</div>

<script src="{{ asset('js/html2pdf.bundle.min.js') }}?v=0.10.1"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const button = document.querySelector('[data-download-receipt-pdf]');
        const receipt = document.querySelector('[data-receipt-document]');

        if (!button || !receipt) return;

        button.addEventListener('click', async () => {
            if (typeof window.html2pdf !== 'function') {
                window.location.href = button.dataset.fallbackUrl;
                return;
            }

            const originalLabel = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span>Menyiapkan PDF...</span>';
            receipt.classList.add('is-pdf-export');

            try {
                if (document.fonts && document.fonts.ready) {
                    await document.fonts.ready;
                }

                await window.html2pdf()
                    .set({
                        margin: [12, 12, 12, 12],
                        filename: button.dataset.filename || 'kwitansi-pembayaran.pdf',
                        image: { type: 'jpeg', quality: 1 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            allowTaint: false,
                            backgroundColor: '#ffffff',
                            logging: false,
                            scrollX: 0,
                            scrollY: 0,
                            // Pertahankan breakpoint desktop seperti tampilan kuitansi print.
                            windowWidth: Math.max(window.innerWidth, 1200),
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait',
                            compress: true,
                        },
                        pagebreak: { mode: ['css', 'legacy'] },
                    })
                    .from(receipt)
                    .save();
            } catch (error) {
                console.error('Gagal membuat PDF kuitansi:', error);
                window.location.href = button.dataset.fallbackUrl;
            } finally {
                receipt.classList.remove('is-pdf-export');
                button.disabled = false;
                button.innerHTML = originalLabel;
            }
        });

        @if($autoDownloadPdf)
            // Tautan Download PDF dari riwayat membuka dokumen yang sama lalu
            // langsung mengekspor elemen kuitansi dengan layout cetaknya.
            window.setTimeout(() => button.click(), 150);
        @endif
    });
</script>
@endsection
