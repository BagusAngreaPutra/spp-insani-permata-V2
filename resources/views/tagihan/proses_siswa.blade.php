@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Proses Tagihan Siswa')

@section('content')
@php
    $adminUser = Auth::guard('web')->user();
    $canGenerate = $adminUser?->hasPermission('tagihan.manage') ?? false;
    $canPay = $adminUser?->hasPermission('pembayaran.process') ?? false;
    $formatRupiah = static fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $className = trim((string) ($siswa->kelas?->nama_kelas ?? ''));
    $classLabel = !$siswa->kelas
        ? 'Kelas belum diatur'
        : (in_array($className, ['', '-', '–'], true)
            ? $siswa->kelas->label_tingkat
            : $siswa->kelas->label_tingkat . ' · ' . $className);
    $studentInitial = strtoupper(substr($siswa->nama, 0, 1));
    $groupedTagihanList = collect($tagihanList)
        ->filter(fn ($item) => $item['is_grouped'])
        ->values();
    $singleTagihanList = collect($tagihanList)
        ->reject(fn ($item) => $item['is_grouped'])
        ->values();
    $singleOutstandingTotal = $singleTagihanList->sum(
        fn ($item) => ($item['is_active'] ?? true)
            ? max(0, (float) ($item['sisa_terhitung'] ?? $item['sisa_bayar']))
            : 0
    );
    $defaultOpenGroup = $groupedTagihanList->search(
        fn ($item) => collect($item['bulan_tagihan'])->contains(
            fn ($month) => ($month['is_active'] ?? true) && (float) $month['sisa_bayar'] > 0
        )
    );
    if ($defaultOpenGroup === false) {
        $defaultOpenGroup = $groupedTagihanList->isNotEmpty() ? 0 : false;
    }
@endphp

@push('page-styles')
<style>
    .payment-page {
        display: grid;
        gap: 18px;
    }

    .payment-heading,
    .payment-heading-actions,
    .payment-student-card,
    .payment-student-profile,
    .payment-student-details,
    .payment-panel-heading,
    .payment-step-title,
    .payment-selection-toolbar,
    .payment-group-header,
    .payment-group-title,
    .payment-group-meta,
    .payment-group-select,
    .payment-summary-bar,
    .payment-summary-copy,
    .payment-selected-item,
    .payment-selected-main,
    .payment-selected-actions,
    .payment-form-actions,
    .payment-discount-info,
    .payment-alert {
        display: flex;
        align-items: center;
    }

    .payment-heading {
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    .payment-eyebrow {
        margin: 0 0 3px;
        color: #98a2b3;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .payment-title {
        margin: 0;
        color: #101828;
        font-size: clamp(25px, 2.6vw, 32px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.16;
    }

    .payment-subtitle {
        margin: 7px 0 0;
        color: #667085;
        font-size: 12px;
    }

    .payment-heading-actions {
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .payment-year-switch {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
        gap: 0;
        height: 42px;
        min-height: 42px;
        padding: 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d8dee9;
        border-radius: 9px;
        box-shadow: 0 2px 5px rgba(16, 24, 40, .04);
    }

    body:has(.app-sidebar) .main-content .payment-page .payment-year-switch label {
        display: inline-flex !important;
        align-items: center;
        align-self: stretch;
        gap: 6px;
        margin: 0 !important;
        padding: 0 11px !important;
        color: #475467 !important;
        background: #f8fafc !important;
        border-right: 1px solid #e4e7ec !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        white-space: nowrap;
    }

    body:has(.app-sidebar) .main-content .payment-page .payment-year-switch label i {
        margin: 0 !important;
        color: #3b82f6 !important;
        font-size: 12px !important;
    }

    body:has(.app-sidebar) .main-content .payment-page .payment-year-switch select {
        width: auto !important;
        min-width: 166px !important;
        height: 40px !important;
        min-height: 40px !important;
        padding: 0 38px 0 12px !important;
        color: #101828 !important;
        background-color: #fff !important;
        border: 0 !important;
        border-radius: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;
        font: inherit;
        font-size: 12.5px !important;
        font-weight: 700 !important;
        cursor: pointer;
    }

    body:has(.app-sidebar) .main-content .payment-page .payment-year-switch select:focus {
        background-color: #f8fbff !important;
        border: 0 !important;
        outline: 0 !important;
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, .15) !important;
    }

    .payment-button,
    .payment-group-toggle,
    .payment-quick-button {
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

    .payment-button {
        min-height: 42px !important;
        padding: 0 15px !important;
        font-size: 12.5px !important;
    }

    body:has(.app-sidebar) .main-content .payment-page .payment-heading-actions .payment-button {
        flex: 0 0 auto;
        min-width: 166px !important;
        height: 42px !important;
        min-height: 42px !important;
        padding: 0 15px !important;
        border-radius: 9px !important;
        font-size: 12.5px !important;
        white-space: nowrap;
    }

    .payment-button.is-primary {
        color: #fff !important;
        background: #1d6b4c !important;
        border-color: #1d6b4c !important;
    }

    .payment-button:hover,
    .payment-quick-button:hover {
        color: #101828 !important;
        background: #f9fafb !important;
    }

    .payment-button.is-primary:hover {
        color: #fff !important;
        background: #15533b !important;
        border-color: #15533b !important;
    }

    .payment-button:disabled {
        color: #98a2b3 !important;
        background: #f2f4f7 !important;
        border-color: #e4e7ec !important;
        cursor: not-allowed !important;
    }

    .payment-alert {
        gap: 10px;
        padding: 11px 13px;
        color: #b42318;
        background: #fef3f2;
        border: 1px solid #fecdca;
        border-radius: 8px;
        font-size: 10.5px;
    }

    .payment-alert i {
        display: grid;
        place-items: center;
        flex: 0 0 30px;
        width: 30px;
        height: 30px;
        background: rgba(255, 255, 255, .7);
        border-radius: 7px;
    }

    .payment-alert.is-success {
        color: #087451;
        background: #ecfdf3;
        border-color: #abefc6;
    }

    .payment-student-card {
        justify-content: space-between;
        gap: 20px;
        padding: 15px 18px;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .payment-student-profile {
        min-width: 0;
        gap: 11px;
    }

    .payment-student-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        color: #1d6b4c;
        background: #f1f9f5;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
    }

    .payment-student-copy {
        min-width: 0;
    }

    .payment-student-copy strong,
    .payment-student-copy span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-student-copy strong {
        color: #101828;
        font-size: 12px;
        font-weight: 650;
    }

    .payment-student-copy span {
        margin-top: 3px;
        color: #667085;
        font-size: 9.5px;
    }

    .payment-student-details {
        justify-content: flex-end;
        gap: 26px;
    }

    .payment-student-detail {
        min-width: 90px;
    }

    .payment-student-detail span,
    .payment-student-detail strong {
        display: block;
    }

    .payment-student-detail span {
        color: #98a2b3;
        font-size: 8.5px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .payment-student-detail strong {
        margin-top: 3px;
        max-width: 210px;
        overflow: hidden;
        color: #344054;
        font-size: 10.5px;
        font-weight: 600;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .payment-stat {
        padding: 15px 16px;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 9px;
    }

    .payment-stat span,
    .payment-stat strong {
        display: block;
    }

    .payment-stat span {
        color: #667085;
        font-size: 9.5px;
    }

    .payment-stat strong {
        margin-top: 5px;
        overflow: hidden;
        color: #101828;
        font-size: clamp(18px, 1.8vw, 23px);
        font-weight: 700;
        letter-spacing: -.035em;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-stat.is-success strong {
        color: #087451;
    }

    .payment-stat.is-danger strong {
        color: #b42318;
    }

    .payment-panel {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .payment-panel[hidden] {
        display: none !important;
    }

    .payment-panel-heading {
        justify-content: space-between;
        gap: 18px;
        min-height: 60px;
        padding: 13px 18px;
        border-bottom: 1px solid #e4e7ec;
    }

    .payment-step-title {
        gap: 9px;
    }

    .payment-step-number {
        display: grid;
        place-items: center;
        width: 25px;
        height: 25px;
        color: #1d6b4c;
        background: #f1f9f5;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 700;
    }

    .payment-panel-title,
    .payment-panel-context {
        margin: 0;
    }

    .payment-panel-title {
        color: #101828;
        font-size: 13px;
        font-weight: 650;
    }

    .payment-panel-context {
        margin-top: 3px;
        color: #667085;
        font-size: 9.5px;
    }

    .payment-selection-toolbar {
        justify-content: space-between;
        gap: 16px;
        min-height: 48px;
        padding: 10px 18px;
        background: #fafbfc;
        border-bottom: 1px solid #e4e7ec;
    }

    .payment-check-label,
    .payment-group-select {
        margin: 0 !important;
        color: #475467 !important;
        font-size: 9.5px !important;
        font-weight: 600 !important;
        cursor: pointer;
    }

    .payment-check-label {
        display: inline-flex !important;
        align-items: center;
        gap: 7px;
    }

    .payment-check,
    .payment-group-check {
        width: 15px !important;
        height: 15px !important;
        margin: 0 !important;
        padding: 0 !important;
        accent-color: #1d6b4c;
        cursor: pointer;
    }

    .payment-selection-hint {
        color: #98a2b3;
        font-size: 9px;
    }

    .payment-bill-list {
        display: grid;
        gap: 12px;
        padding: 14px;
        background: #f6f8fc;
    }

    .payment-group,
    .payment-single-section {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid #dfe5ee;
        border-radius: 11px;
    }

    .payment-group {
        box-shadow: 0 2px 8px rgba(34, 48, 74, .035);
    }

    .payment-group.is-open {
        border-color: #bdc9fb;
        box-shadow: 0 7px 20px rgba(79, 70, 229, .075);
    }

    .payment-group-header {
        justify-content: space-between;
        gap: 20px;
        min-height: 68px;
        padding: 12px 14px;
        background: #fcfdff;
    }

    .payment-group-title {
        appearance: none;
        flex: 1 1 auto;
        min-width: 0;
        gap: 9px;
        padding: 3px !important;
        color: inherit !important;
        text-align: left !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 7px !important;
        cursor: pointer;
    }

    .payment-group-title:hover,
    .payment-group-title:focus-visible {
        background: #f2f7ff !important;
    }

    .payment-group-title:focus-visible {
        outline: 2px solid rgba(37, 132, 93, .28);
        outline-offset: 2px;
    }

    .payment-group-toggle {
        display: grid;
        place-items: center;
        flex: 0 0 32px;
        width: 32px !important;
        height: 32px !important;
        min-height: 0 !important;
        padding: 0 !important;
        color: #1d6b4c !important;
        border: 1px solid #d6e4fb !important;
        background: #f1f9f5 !important;
        border-radius: 8px;
        font-size: 9px !important;
    }

    .payment-group.is-open .payment-group-toggle i {
        transform: rotate(180deg);
    }

    .payment-group-toggle i {
        transition: transform .15s ease;
    }

    .payment-group-copy {
        min-width: 0;
    }

    .payment-group-copy strong,
    .payment-group-copy span {
        display: block;
    }

    .payment-group-copy strong {
        overflow: hidden;
        color: #101828;
        font-size: 11px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-group-copy span {
        margin-top: 2px;
        color: #98a2b3;
        font-size: 8.5px;
    }

    .payment-group-subline,
    .payment-single-subline {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px 8px;
        margin-top: 3px;
    }

    .payment-group-subline > span,
    .payment-single-subline > span {
        margin-top: 0;
    }

    .payment-row-kind {
        display: inline-flex !important;
        align-items: center;
        gap: 4px;
        min-height: 19px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 7.5px !important;
        font-weight: 650;
        line-height: 1;
        white-space: nowrap;
    }

    .payment-row-kind.is-expandable {
        color: #1d6b4c;
        background: #f1f9f5;
        border: 1px solid #d6e4fb;
    }

    .payment-row-kind.is-static {
        color: #667085;
        background: #f2f4f7;
        border: 1px solid #e4e7ec;
    }

    .payment-row-kind i {
        font-size: 7px;
    }

    .payment-group .payment-kind-open {
        display: none;
    }

    .payment-group.is-open .payment-kind-closed {
        display: none;
    }

    .payment-group.is-open .payment-kind-open {
        display: inline;
    }

    .payment-group-meta {
        justify-content: flex-end;
        flex: 0 0 auto;
        gap: 12px;
    }

    .payment-group-amount {
        min-width: 118px;
        text-align: right;
    }

    .payment-group-amount span,
    .payment-group-amount strong {
        display: block;
    }

    .payment-group-amount span {
        color: #98a2b3;
        font-size: 8px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .payment-group-amount strong {
        margin-top: 2px;
        color: #b42318;
        font-size: 10.5px;
        font-weight: 650;
    }

    .payment-group-select {
        justify-content: center;
        gap: 7px;
        min-width: 104px;
        min-height: 34px;
        padding: 0 10px;
        background: #f9fafb;
        border: 1px solid #e4e7ec;
        border-radius: 6px;
        white-space: nowrap;
    }

    .payment-group-body[hidden] {
        display: none !important;
    }

    .payment-bill-table {
        width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .payment-bill-table .payment-col-check { width: 5%; }
    .payment-bill-table .payment-col-period { width: 18%; }
    .payment-bill-table .payment-col-due { width: 15%; }
    .payment-bill-table .payment-col-nominal { width: 12%; }
    .payment-bill-table .payment-col-paid { width: 13%; }
    .payment-bill-table .payment-col-remaining { width: 12%; }
    .payment-bill-table .payment-col-status { width: 18%; }
    .payment-bill-table .payment-col-action { width: 7%; }

    .payment-bill-table th {
        height: 42px !important;
        padding: 8px 12px !important;
        color: #667085 !important;
        background: #f9fafb !important;
        border-top: 1px solid #e4e7ec !important;
        border-bottom: 1px solid #e4e7ec !important;
        font-size: 8.5px !important;
        font-weight: 600 !important;
    }

    .payment-bill-table td {
        height: 54px !important;
        padding: 9px 12px !important;
        color: #344054 !important;
        background: #fff !important;
        border-bottom: 1px solid #eef0f3 !important;
        font-size: 9.5px !important;
    }

    .payment-bill-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .payment-bill-table tbody tr:hover td {
        background: #fbfcfe !important;
    }

    .payment-bill-check-cell {
        text-align: center !important;
    }

    .payment-bill-action-cell {
        text-align: center !important;
    }

    .payment-activation-form {
        display: inline-flex;
        margin: 0;
    }

    .payment-activation-button {
        display: inline-grid !important;
        place-items: center !important;
        flex: 0 0 25px;
        width: 25px !important;
        height: 25px !important;
        min-height: 25px !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #667085 !important;
        background: rgba(102, 112, 133, .06) !important;
        border: 1px solid rgba(102, 112, 133, .18) !important;
        border-radius: 6px !important;
        font-size: 8px !important;
        opacity: .52;
        cursor: pointer;
        transition: opacity .16s ease, background-color .16s ease, border-color .16s ease;
    }

    .payment-activation-button.is-inactive {
        color: #47705f !important;
        background: rgba(71, 112, 95, .07) !important;
        border-color: rgba(71, 112, 95, .2) !important;
        opacity: .64;
    }

    .payment-activation-button:hover {
        background: rgba(102, 112, 133, .11) !important;
        border-color: rgba(102, 112, 133, .3) !important;
        opacity: .86;
    }

    .payment-activation-button:focus-visible {
        outline: 2px solid rgba(102, 112, 133, .32);
        outline-offset: 2px;
        opacity: 1;
    }

    .payment-row-inactive td,
    .payment-single.is-inactive {
        background: #f8fafc !important;
    }

    .payment-bill-table :is(th, td):nth-child(4),
    .payment-bill-table :is(th, td):nth-child(5),
    .payment-bill-table :is(th, td):nth-child(6) {
        text-align: right !important;
    }

    .payment-bill-table :is(th, td):nth-child(7),
    .payment-bill-table :is(th, td):nth-child(8) {
        text-align: center !important;
    }

    .payment-bill-table td:nth-child(5) .payment-paid-cell {
        margin-left: auto;
    }

    .payment-money {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .payment-money.is-remaining {
        color: #b42318;
        font-weight: 650;
    }

    .payment-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-height: 22px;
        padding: 0 7px;
        color: #b42318;
        background: #fef3f2;
        border-radius: 999px;
        font-size: 8px;
        font-weight: 650;
        white-space: nowrap;
    }

    .payment-status::before {
        width: 5px;
        height: 5px;
        background: #f04438;
        border-radius: 50%;
        content: "";
    }

    .payment-status.is-paid {
        color: #087451;
        background: #ecfdf3;
    }

    .payment-status.is-paid::before {
        background: #12b76a;
    }

    .payment-status.is-upcoming {
        color: #a15c07;
        background: #fff7d6;
        border: 1px solid #f4c84a;
    }

    .payment-status.is-upcoming::before {
        background: #eaaa08;
    }

    .payment-status.is-partial {
        color: #b54708;
        background: #fffaeb;
    }

    .payment-status.is-partial::before {
        background: #f79009;
    }

    .payment-status.is-inactive {
        color: #475467;
        background: #f2f4f7;
        border: 1px solid #d0d5dd;
    }

    .payment-status.is-inactive::before {
        background: #98a2b3;
    }

    .payment-single-section {
        box-shadow: 0 2px 8px rgba(34, 48, 74, .035);
    }

    .payment-single-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 68px;
        padding: 12px 14px;
        background: #fff;
        border-bottom: 1px solid #e5e9f0;
    }

    .payment-single-section-copy {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .payment-single-section-icon {
        display: grid;
        place-items: center;
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        margin: 0;
        color: #9a6848;
        background: #fbf7f3;
        border: 1px solid #eadfd5;
        border-radius: 8px;
        font-size: 9px;
    }

    .payment-single-section-copy > div > strong,
    .payment-single-section-copy > div > span {
        display: block;
    }

    .payment-single-section-copy > div > strong {
        color: #101828;
        font-size: 10.5px;
        font-weight: 700;
    }

    .payment-single-section-copy > div > span {
        margin-top: 2px;
        color: #98a2b3;
        font-size: 8.5px;
    }

    .payment-single-section-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
    }

    .payment-single-section-balance {
        min-width: 118px;
        text-align: right;
    }

    .payment-single-section-balance span,
    .payment-single-section-balance strong {
        display: block;
    }

    .payment-single-section-balance span {
        color: #98a2b3;
        font-size: 8px;
        font-weight: 650;
        text-transform: uppercase;
    }

    .payment-single-section-balance strong {
        margin-top: 2px;
        color: #b42318;
        font-size: 10.5px;
        font-weight: 700;
    }

    .payment-single-count {
        display: inline-flex;
        align-items: center;
        min-height: 23px;
        padding: 0 8px;
        color: #6d28d9;
        background: #f3e8ff;
        border: 1px solid #e4d1ff;
        border-radius: 999px;
        font-size: 8px;
        font-weight: 700;
    }

    .payment-single-table {
        overflow-x: auto;
    }

    .payment-single-grid {
        display: grid;
        grid-template-columns: 5% 18% 15% 12% 13% 12% 18% 7%;
        align-items: center;
        min-width: 0;
    }

    .payment-single-head {
        min-height: 42px;
        color: #667085;
        background: #f7f8fc;
        border-bottom: 1px solid #e4e8ef;
        font-size: 8.5px;
        font-weight: 650;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .payment-single-head > span {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        padding: 8px 12px;
    }

    .payment-single-head > span:nth-child(n+4):nth-child(-n+6) {
        justify-content: flex-end;
    }

    .payment-single-head > span:nth-child(7),
    .payment-single-head > span:nth-child(8) {
        justify-content: center;
    }

    .payment-column-icon {
        display: inline-grid;
        place-items: center;
        flex: 0 0 21px;
        width: 21px;
        height: 21px;
        color: #4f46e5;
        background: #e4e7ff;
        border-radius: 6px;
        font-size: 8px;
    }

    .payment-single-head > span:nth-child(2) .payment-column-icon {
        color: #7c3aed;
        background: #f0e5ff;
    }

    .payment-single-head > span:nth-child(3) .payment-column-icon {
        color: #0891b2;
        background: #dff8fc;
    }

    .payment-single-head > span:nth-child(4) .payment-column-icon {
        color: #ea580c;
        background: #fff0da;
    }

    .payment-single-head > span:nth-child(5) .payment-column-icon {
        color: #db2777;
        background: #ffe4f0;
    }

    .payment-single-head > span:nth-child(7) .payment-column-icon {
        color: #7c3aed;
        background: #f0e5ff;
    }

    .payment-single > div {
        min-width: 0;
        padding: 9px 12px;
    }

    .payment-single-head > span:nth-child(n+4):nth-child(-n+6),
    .payment-single > div:nth-child(n+4):nth-child(-n+6) {
        text-align: right;
    }

    .payment-single-head > span:nth-child(7),
    .payment-single-head > span:nth-child(8),
    .payment-single > div:nth-child(7),
    .payment-single > div:nth-child(8) {
        text-align: center;
    }

    .payment-single {
        min-height: 54px;
        background: #fff;
        border-bottom: 1px solid #edf0f4;
    }

    .payment-single:last-child {
        border-bottom: 0;
    }

    .payment-single:hover {
        background: #fafbff;
    }

    .payment-single-check {
        display: grid;
        place-items: center;
    }

    .payment-single-nominal .payment-single-value strong {
        color: #4338ca;
        font-weight: 750;
    }

    .payment-single-paid .payment-paid-cell {
        margin-left: auto;
    }

    .payment-single-paid .payment-money {
        color: #047857;
        font-weight: 700;
    }

    .payment-single-label {
        display: none;
        color: #98a2b3;
        font-size: 8px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .payment-single-name strong,
    .payment-single-name span,
    .payment-single-value strong,
    .payment-single-value span {
        display: block;
    }

    .payment-single-name strong {
        overflow: hidden;
        color: #101828;
        font-size: 10.5px;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-single-name span,
    .payment-single-value span {
        margin-top: 2px;
        color: #98a2b3;
        font-size: 8.5px;
    }

    .payment-single-subline .payment-row-kind {
        margin-top: 0;
    }

    .payment-single-value strong {
        color: #344054;
        font-size: 9.5px;
        font-weight: 600;
    }

    .payment-summary-bar {
        position: sticky;
        top: 68px;
        z-index: 20;
        justify-content: space-between;
        gap: 18px;
        min-height: 64px;
        padding: 11px 18px;
        background: rgba(255, 255, 255, .97);
        border-top: 1px solid #e4e7ec;
        backdrop-filter: blur(10px);
    }

    .payment-summary-copy {
        gap: 0;
    }

    .payment-summary-item {
        min-width: 104px;
        padding-right: 18px;
    }

    .payment-summary-item + .payment-summary-item {
        padding-left: 18px;
        border-left: 1px solid #e4e7ec;
    }

    .payment-summary-item span,
    .payment-summary-item strong {
        display: block;
    }

    .payment-summary-item span {
        color: #98a2b3;
        font-size: 8.5px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .payment-summary-item strong {
        margin-top: 3px;
        color: #101828;
        font-size: 11px;
        font-weight: 650;
    }

    .payment-summary-item .payment-summary-value {
        display: flex;
        align-items: baseline;
        gap: 4px;
        white-space: nowrap;
    }

    .payment-summary-item .payment-summary-value span {
        display: inline;
        margin: 0;
        color: inherit;
        font: inherit;
        letter-spacing: inherit;
        text-transform: none;
    }

    .payment-form-body {
        display: grid;
        gap: 16px;
        padding: 16px 18px 18px;
    }

    .payment-selected-list {
        display: grid;
        gap: 8px;
    }

    .payment-selected-item {
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 11px 12px;
        background: #f9fafb;
        border: 1px solid #e4e7ec;
        border-radius: 8px;
    }

    .payment-selected-main {
        align-items: flex-start;
        min-width: 0;
        gap: 9px;
    }

    .payment-selected-icon {
        display: grid;
        place-items: center;
        flex: 0 0 30px;
        width: 30px;
        height: 30px;
        color: #1d6b4c;
        background: #f1f9f5;
        border-radius: 7px;
        font-size: 10px;
    }

    .payment-selected-copy {
        min-width: 0;
    }

    .payment-selected-copy strong,
    .payment-selected-copy span {
        display: block;
    }

    .payment-selected-copy strong {
        overflow: hidden;
        color: #101828;
        font-size: 10.5px;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .payment-selected-copy span {
        margin-top: 3px;
        color: #667085;
        font-size: 9px;
    }

    .payment-selected-actions {
        align-items: flex-end;
        gap: 7px;
    }

    .payment-amount-field {
        min-width: 180px;
    }

    .payment-amount-field label {
        margin-bottom: 4px !important;
        color: #667085 !important;
        font-size: 8.5px !important;
    }

    .payment-amount-field input {
        width: 180px !important;
        height: 34px !important;
        margin: 0 !important;
        padding: 0 10px !important;
        color: #101828 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 6px !important;
        box-shadow: none !important;
        font-size: 10.5px !important;
        font-weight: 600 !important;
    }

    .payment-amount-field input.is-invalid {
        border-color: #f04438 !important;
    }

    .payment-input-error {
        display: none !important;
        margin-top: 4px;
        color: #b42318;
        font-size: 8.5px;
    }

    .payment-input-error.is-visible {
        display: block !important;
    }

    .payment-quick-button {
        min-height: 34px !important;
        padding: 0 9px !important;
        font-size: 9px !important;
        white-space: nowrap;
    }

    .payment-discount-info {
        display: none;
        grid-column: 1 / -1;
        gap: 7px;
        width: 100%;
        margin-top: 7px;
        padding: 8px 9px;
        color: #087451;
        background: #ecfdf3;
        border-radius: 6px;
        font-size: 8.5px;
    }

    .payment-discount-info.is-visible {
        display: flex;
    }

    .payment-discount-info.is-warning {
        color: #b54708;
        background: #fffaeb;
    }

    .payment-details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 11px;
        padding-top: 2px;
    }

    .payment-field.is-full {
        grid-column: 1 / -1;
    }

    .payment-field label {
        margin-bottom: 5px !important;
        color: #475467 !important;
        font-size: 9.5px !important;
    }

    .payment-field-error {
        display: block;
        margin-top: 5px;
        color: #b42318;
        font-size: 9px;
        font-weight: 600;
        line-height: 1.35;
    }

    .payment-field input,
    .payment-field select,
    .payment-field textarea {
        width: 100% !important;
        margin: 0 !important;
        color: #344054 !important;
        background: #fff !important;
        border: 1px solid #d0d5dd !important;
        border-radius: 7px !important;
        box-shadow: none !important;
        font-size: 10.5px !important;
    }

    .payment-field input,
    .payment-field select {
        height: 37px !important;
    }

    .payment-field textarea {
        min-height: 72px !important;
        padding: 9px 10px !important;
        resize: vertical;
    }

    .payment-form-footer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: end;
        gap: 16px;
        padding-top: 4px;
    }

    .payment-form-total span,
    .payment-form-total strong {
        display: block;
    }

    .payment-form-total span {
        color: #667085;
        font-size: 9px;
    }

    .payment-form-total strong {
        margin-top: 3px;
        color: #101828;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: -.035em;
    }

    .payment-form-actions {
        justify-content: flex-end;
        gap: 8px;
    }

    .payment-empty {
        display: grid;
        place-items: center;
        min-height: 250px;
        padding: 30px;
        color: #667085;
        text-align: center;
    }

    .payment-empty i {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        margin: 0 auto 10px;
        color: #667085;
        background: #f2f4f7;
        border-radius: 9px;
        font-size: 14px;
    }

    .payment-empty strong,
    .payment-empty span {
        display: block;
    }

    .payment-empty strong {
        color: #344054;
        font-size: 12px;
    }

    .payment-empty span {
        max-width: 350px;
        margin-top: 4px;
        font-size: 9.5px;
    }

    .payment-modal-list {
        display: grid;
        gap: 7px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .payment-modal-list li {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #344054;
        font-size: 10.5px;
    }

    .payment-modal-list i {
        color: #12a06a;
        font-size: 9px;
    }

    .payment-modal-note {
        display: flex;
        gap: 8px;
        margin-top: 14px;
        padding: 9px 10px;
        color: #854a0e;
        background: #fffaeb;
        border: 1px solid #fef0c7;
        border-radius: 7px;
        font-size: 9.5px;
    }

    @media (max-width: 1050px) {
        .payment-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .payment-heading-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .payment-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .payment-group-meta {
            gap: 10px;
        }

        .payment-single-grid {
            min-width: 0;
        }
    }

    @media (max-width: 760px) {
        .payment-page {
            gap: 14px;
        }

        .payment-heading-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .payment-year-switch {
            grid-column: 1 / -1;
            justify-content: space-between;
            width: 100%;
        }

        body:has(.app-sidebar) .main-content .payment-page .payment-year-switch select {
            flex: 1 1 auto;
        }

        body:has(.app-sidebar) .main-content .payment-page .payment-heading-actions .payment-button {
            min-width: 0 !important;
            width: 100% !important;
        }

        .payment-student-card,
        .payment-panel-heading,
        .payment-selection-toolbar,
        .payment-summary-bar,
        .payment-selected-item,
        .payment-form-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .payment-summary-bar {
            top: 62px;
        }

        .payment-student-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            gap: 10px;
            padding-top: 12px;
            border-top: 1px solid #eef0f3;
        }

        .payment-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .payment-stat {
            padding: 12px;
        }

        .payment-stat strong {
            font-size: 17px;
        }

        .payment-selection-hint {
            display: none;
        }

        .payment-group-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .payment-single-section-title {
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
        }

        .payment-single-section-meta {
            justify-content: space-between;
            width: 100%;
            padding-left: 41px;
        }

        .payment-single-section-balance {
            min-width: 0;
            text-align: left;
        }

        .payment-group-title {
            width: 100%;
        }

        .payment-group-meta {
            justify-content: space-between;
            width: 100%;
            padding-left: 37px;
        }

        .payment-group-amount {
            min-width: 0;
            text-align: left;
        }

        .payment-group-select {
            margin-left: auto !important;
        }

        .payment-group-body {
            padding: 8px;
            background: #f9fafb;
            border-top: 1px solid #e4e7ec;
        }

        body:has(.app-sidebar) .payment-page .payment-bill-table {
            display: block !important;
            min-width: 0 !important;
            background: transparent !important;
        }

        .payment-bill-table thead {
            display: none !important;
        }

        .payment-bill-table tbody,
        .payment-bill-table tr,
        .payment-bill-table td {
            display: block !important;
            width: 100% !important;
        }

        .payment-bill-table tr {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            margin-bottom: 7px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: 7px;
        }

        .payment-bill-table tr:last-child {
            margin-bottom: 0;
        }

        .payment-bill-table td {
            display: grid !important;
            grid-template-columns: 92px minmax(0, 1fr) !important;
            align-items: center !important;
            min-height: 34px !important;
            height: auto !important;
            padding: 7px 9px !important;
            text-align: right !important;
            border-bottom: 1px solid #eef0f3 !important;
        }

        .payment-bill-table td::before {
            color: #98a2b3;
            font-size: 8px;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            content: attr(data-label);
        }

        .payment-bill-table td.payment-bill-check-cell {
            display: grid !important;
            grid-column: 1;
            grid-row: 1 / span 7;
            place-items: center !important;
            padding: 0 !important;
            border-right: 1px solid #eef0f3 !important;
            border-bottom: 0 !important;
        }

        .payment-bill-table td.payment-bill-check-cell::before {
            display: none;
        }

        .payment-bill-table td:not(.payment-bill-check-cell) {
            grid-column: 2;
        }

        .payment-single-table {
            overflow: visible;
        }

        .payment-single-head {
            display: none;
        }

        .payment-single-grid,
        .payment-single {
            min-width: 0;
        }

        .payment-single {
            grid-template-columns: 30px minmax(0, 1fr);
            padding: 8px 8px 8px 0;
        }

        .payment-single > div:not(.payment-single-check):not(.payment-single-name) {
            display: grid;
            grid-column: 2;
            grid-template-columns: 92px minmax(0, 1fr);
            align-items: center;
            min-height: 28px;
            text-align: right;
        }

        .payment-single-label {
            display: block;
            text-align: left;
        }

        .payment-single-name {
            grid-column: 2;
        }

        .payment-single-check {
            grid-row: 1 / span 7;
            align-self: stretch;
            display: grid;
            place-items: center;
            border-right: 1px solid #eef0f3;
        }

        .payment-bill-action-cell .payment-activation-form,
        .payment-single-action .payment-activation-form {
            justify-self: end;
        }

        .payment-summary-copy {
            width: 100%;
            justify-content: space-between;
        }

        .payment-summary-item {
            flex: 1 1 50%;
            min-width: 0;
        }

        .payment-summary-bar .payment-button {
            width: 100% !important;
        }

        .payment-selected-item {
            display: grid;
        }

        .payment-selected-actions {
            align-items: flex-end;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            width: 100%;
        }

        .payment-amount-field,
        .payment-amount-field input {
            width: 100% !important;
            min-width: 0;
        }

        .payment-details-grid,
        .payment-form-footer {
            grid-template-columns: 1fr;
        }

        .payment-field.is-full {
            grid-column: auto;
        }

        .payment-form-actions {
            display: grid;
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        .payment-heading-actions {
            grid-template-columns: 1fr;
        }

        .payment-year-switch {
            grid-column: auto;
        }
    }

    @media (max-width: 360px) {
        .payment-stats,
        .payment-student-details {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="payment-page">
            @if(session('success'))
                <div class="payment-alert is-success" role="status">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="payment-alert" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="payment-alert" role="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <header class="payment-heading">
                <div>
                    <p class="payment-eyebrow"><i class="fas fa-receipt" aria-hidden="true"></i>Tagihan · Proses pembayaran</p>
                    <h1 class="payment-title" data-page-title>{{ $siswa->nama }}</h1>
                    <p class="payment-subtitle">Pilih satu atau beberapa tagihan, lalu atur jumlah pembayarannya.</p>
                </div>

                <div class="payment-heading-actions">
                    @if($academicYearData->isNotEmpty())
                        <form class="payment-year-switch" method="GET" action="{{ route('tagihan.proses.siswa', $siswa->id) }}">
                            <label for="paymentAcademicYear">
                                <i class="fas fa-calendar-days" aria-hidden="true"></i>
                                <span>Tahun ajaran</span>
                            </label>
                            <select id="paymentAcademicYear" name="tahun_ajaran" onchange="this.form.submit()">
                                @foreach($academicYearData as $tahun)
                                    <option value="{{ $tahun->id }}" {{ $selectedTahunAjaran?->id === $tahun->id ? 'selected' : '' }}>
                                        {{ $tahun->label }}{{ $tahun->aktif ? ' · Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <a class="payment-button pi-tone-back" href="{{ route('tagihan.index.grouped', ['sekolah' => $siswa->id_sekolah, 'kelas' => $siswa->kelas_id, 'tahun_ajaran' => $selectedTahunAjaran?->id]) }}">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali ke tagihan</span>
                    </a>
                    @if($canGenerate)
                        <button class="payment-button is-primary pi-tone-success" type="button" data-bs-toggle="modal" data-bs-target="#generateStudentModal">
                            <i class="fas fa-file-circle-plus"></i>
                            <span>Buat tagihan siswa</span>
                        </button>
                    @endif
                </div>
            </header>

            <section class="payment-student-card" aria-label="Informasi siswa">
                <div class="payment-student-profile">
                    <span class="payment-student-avatar">{{ $studentInitial }}</span>
                    <span class="payment-student-copy">
                        <strong>{{ $siswa->nama }}</strong>
                        <span>NIS {{ $siswa->nis }}</span>
                    </span>
                </div>
                <div class="payment-student-details">
                    <div class="payment-student-detail">
                        <span><i class="fas fa-chalkboard" aria-hidden="true"></i>Kelas</span>
                        <strong>{{ $classLabel }}</strong>
                    </div>
                    <div class="payment-student-detail">
                        <span><i class="fas fa-school" aria-hidden="true"></i>Sekolah</span>
                        <strong>{{ $siswa->sekolah?->nama_sekolah ?? '-' }}</strong>
                    </div>
                    <div class="payment-student-detail">
                        <span><i class="fas fa-calendar-days" aria-hidden="true"></i>Tahun ajaran</span>
                        <strong>{{ $selectedTahunAjaran?->label ?? '-' }}</strong>
                    </div>
                </div>
            </section>

            <section class="payment-stats" aria-label="Ringkasan tagihan siswa">
                <article class="payment-stat">
                    <span class="payment-stat-label"><i class="fas fa-file-invoice payment-stat-icon" aria-hidden="true"></i>Total tagihan</span>
                    <strong>{{ number_format($studentSummary['total_tagihan'], 0, ',', '.') }}</strong>
                </article>
                <article class="payment-stat is-nominal">
                    <span class="payment-stat-label"><i class="fas fa-coins payment-stat-icon" aria-hidden="true"></i>Total nominal</span>
                    <strong>{{ $formatRupiah($studentSummary['total_nominal']) }}</strong>
                </article>
                <article class="payment-stat is-success">
                    <span class="payment-stat-label"><i class="fas fa-circle-check payment-stat-icon" aria-hidden="true"></i>Sudah dibayar</span>
                    <strong>{{ $formatRupiah($studentSummary['total_dibayar']) }}</strong>
                </article>
                <article class="payment-stat is-danger">
                    <span class="payment-stat-label"><i class="fas fa-hourglass-half payment-stat-icon" aria-hidden="true"></i>Sisa tagihan</span>
                    <strong>{{ $formatRupiah($studentSummary['sisa_bayar']) }}</strong>
                </article>
            </section>

            <section class="payment-panel" id="billSelectionPanel">
                <div class="payment-panel-heading">
                    <div class="payment-step-title">
                        <span class="payment-step-number">1</span>
                        <div>
                            <h2 class="payment-panel-title">Pilih tagihan</h2>
                                <p class="payment-panel-context">
                                    {{ $studentSummary['belum_lunas'] }} tagihan masih perlu dibayar
                                    @if($studentSummary['tidak_aktif'] > 0)
                                        &middot; {{ $studentSummary['tidak_aktif'] }} tidak aktif dan tidak dihitung
                                    @endif
                                </p>
                        </div>
                    </div>
                </div>

                @if(count($tagihanList) > 0)
                    <div class="payment-selection-toolbar">
                        <label class="payment-check-label" for="selectAllBills">
                            <input class="payment-check" id="selectAllBills" type="checkbox">
                            <span>Pilih semua tagihan belum lunas</span>
                        </label>
                            <span class="payment-selection-hint"><i class="fas fa-lock" aria-hidden="true"></i>Tagihan lunas atau tidak aktif tidak dapat dipilih.</span>
                    </div>

                    <div class="payment-summary-bar">
                        <div class="payment-summary-copy">
                            <div class="payment-summary-item">
                                <span>Dipilih</span>
                                <strong class="payment-summary-value">
                                    <span id="selectedBillCount">0</span>
                                    <span>tagihan</span>
                                </strong>
                            </div>
                            <div class="payment-summary-item">
                                <span>Total sisa</span>
                                <strong id="selectedBillTotal">Rp0</strong>
                            </div>
                        </div>
                        @if($canPay)
                            <button class="payment-button is-primary" id="continuePaymentButton" type="button" disabled>
                                <i class="fas fa-arrow-right"></i>
                                <span>Lanjut pembayaran</span>
                            </button>
                        @endif
                    </div>

                    <div class="payment-bill-list">
                        @foreach($groupedTagihanList as $tagihan)
                                @php
                                    $groupId = 'payment-group-' . $loop->index;
                                    $groupOpen = $loop->index === $defaultOpenGroup;
                                    $outstandingMonths = collect($tagihan['bulan_tagihan'])
                                        ->filter(fn ($bulan) => ($bulan['is_active'] ?? true) && (float) $bulan['sisa_bayar'] > 0);
                                    $inactiveMonths = collect($tagihan['bulan_tagihan'])->where('is_active', false)->count();
                                @endphp
                                <article class="payment-group {{ $groupOpen ? 'is-open' : '' }}" data-payment-group>
                                    <header class="payment-group-header">
                                        <button
                                            class="payment-group-title"
                                            type="button"
                                            data-group-toggle="{{ $groupId }}"
                                            aria-controls="{{ $groupId }}"
                                            aria-expanded="{{ $groupOpen ? 'true' : 'false' }}"
                                        >
                                            <span class="payment-group-toggle" aria-hidden="true">
                                                <i class="fas fa-chevron-down"></i>
                                            </span>
                                            <div class="payment-group-copy">
                                                <strong>{{ $tagihan['nama_tagihan'] }}</strong>
                                                <div class="payment-group-subline">
                                                    <span>
                                                        {{ count($tagihan['bulan_tagihan']) }} periode
                                                        &middot; Tahun ajaran {{ $tagihan['academic_year_label'] }}
                                                        @if($inactiveMonths > 0)
                                                            &middot; {{ $inactiveMonths }} tidak aktif
                                                        @endif
                                                    </span>
                                                    <span class="payment-row-kind is-expandable">
                                                        <i class="fas fa-table-list" aria-hidden="true"></i>
                                                        <span class="payment-kind-closed">Buka rincian</span>
                                                        <span class="payment-kind-open">Tutup rincian</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </button>

                                        <div class="payment-group-meta">
                                            <span class="payment-group-amount">
                                                <span>Sisa</span>
                                                <strong>{{ $formatRupiah(max(0, $tagihan['sisa_terhitung'] ?? $tagihan['sisa_bayar'])) }}</strong>
                                            </span>
                                            @if($outstandingMonths->isNotEmpty())
                                                <label class="payment-group-select">
                                                    <input class="payment-group-check" type="checkbox" data-select-group="{{ $groupId }}">
                                                    <span>Pilih grup</span>
                                                </label>
                                            @elseif($tagihan['status'] === 'tidak_aktif')
                                                <span class="payment-status is-inactive">Tidak aktif</span>
                                            @else
                                                <span class="payment-status is-paid">Lunas</span>
                                            @endif
                                        </div>
                                    </header>

                                    <div class="payment-group-body" id="{{ $groupId }}" {{ $groupOpen ? '' : 'hidden' }}>
                                        <table class="payment-bill-table">
                                            <colgroup>
                                                <col class="payment-col-check">
                                                <col class="payment-col-period">
                                                <col class="payment-col-due">
                                                <col class="payment-col-nominal">
                                                <col class="payment-col-paid">
                                                <col class="payment-col-remaining">
                                                <col class="payment-col-status">
                                                <col class="payment-col-action">
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Periode</th>
                                                    <th>Jatuh tempo</th>
                                                    <th>Nominal</th>
                                                    <th>Dibayar</th>
                                                    <th>Sisa</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tagihan['bulan_tagihan'] as $bulan)
                                                    @php
                                                        $monthIsActive = (bool) ($bulan['is_active'] ?? true);
                                                        $monthOutstanding = $monthIsActive && (float) $bulan['sisa_bayar'] > 0;
                                                        $monthDueOutstanding = (float) ($bulan['sisa_terhitung'] ?? $bulan['sisa_bayar']) > 0;
                                                        $monthNominal = (float) $bulan['nominal'];
                                                        $monthPaid = (float) $bulan['total_bayar'];
                                                        [$monthStatusLabel, $monthStatusClass] = match($bulan['status']) {
                                                            'tidak_aktif' => ['Tidak aktif', 'is-inactive'],
                                                            'lunas' => ['Lunas', 'is-paid'],
                                                            'belum_jatuh_tempo' => ['Belum jatuh tempo', 'is-upcoming'],
                                                            'sebagian' => ['Sebagian', 'is-partial'],
                                                            default => ['Belum lunas', ''],
                                                        };
                                                        $paymentProgress = $monthNominal > 0
                                                            ? min(100, (int) round(($monthPaid / $monthNominal) * 100))
                                                            : 0;
                                                        $dueDate = $bulan['tanggal_jatuh_tempo']
                                                            ? \Carbon\Carbon::parse($bulan['tanggal_jatuh_tempo'])
                                                            : null;
                                                    @endphp
                                                    <tr class="{{ $monthIsActive ? '' : 'payment-row-inactive' }}">
                                                        <td class="payment-bill-check-cell">
                                                            @if($monthOutstanding)
                                                                <input
                                                                    class="payment-check bill-checkbox"
                                                                    type="checkbox"
                                                                    value="{{ $bulan['id'] }}"
                                                                    data-group="{{ $groupId }}"
                                                                    data-name="{{ $tagihan['nama_tagihan'] }} · {{ $bulan['periode_display'] ?? $bulan['periode'] }}"
                                                                    data-nominal="{{ (float) $bulan['sisa_bayar'] }}"
                                                                    data-periode="{{ $dueDate?->format('Y-m') }}"
                                                                    data-deadline="{{ $dueDate?->format('Y-m-d') }}"
                                                                    data-is-spp="{{ str_contains(strtolower($tagihan['nama_tagihan']), 'spp') ? 'true' : 'false' }}"
                                                                >
                                                            @elseif($monthIsActive)
                                                                <i class="fas fa-check" style="color:#12a06a;font-size:9px"></i>
                                                            @endif
                                                        </td>
                                                        <td data-label="Periode">{{ $bulan['periode_display'] ?? $bulan['periode'] }}</td>
                                                        <td data-label="Jatuh tempo">{{ $dueDate?->translatedFormat('d M Y') ?? '-' }}</td>
                                                        <td data-label="Nominal"><span class="payment-money">{{ $formatRupiah($bulan['nominal']) }}</span></td>
                                                        <td data-label="Dibayar">
                                                            <span class="payment-paid-cell">
                                                                <span class="payment-money">{{ $formatRupiah($bulan['total_bayar']) }}</span>
                                                                <span class="pi-progress {{ $paymentProgress >= 100 ? 'is-success' : '' }}" role="progressbar" aria-label="Pembayaran {{ $paymentProgress }} persen" aria-valuenow="{{ $paymentProgress }}" aria-valuemin="0" aria-valuemax="100">
                                                                    <i style="width: {{ $paymentProgress }}%"></i>
                                                                </span>
                                                            </span>
                                                        </td>
                                                        <td data-label="Sisa"><span class="payment-money {{ $monthDueOutstanding ? 'is-remaining' : '' }}">{{ $formatRupiah(max(0, $bulan['sisa_terhitung'] ?? $bulan['sisa_bayar'])) }}</span></td>
                                                        <td data-label="Status">
                                                            <span class="payment-status {{ $monthStatusClass }}">{{ $monthStatusLabel }}</span>
                                                        </td>
                                                        <td class="payment-bill-action-cell" data-label="Aksi">
                                                            @if($canGenerate)
                                                                <form class="payment-activation-form" method="POST" action="{{ route('tagihan.toggle-active', $bulan['id']) }}" data-confirm-title="Konfirmasi status tagihan" data-confirm-message="{{ $monthIsActive ? 'Nonaktifkan tagihan ini? Nominalnya tidak akan dihitung.' : 'Aktifkan kembali tagihan ini?' }}" data-confirm-label="{{ $monthIsActive ? 'Nonaktifkan' : 'Aktifkan' }}" data-confirm-tone="warning">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button
                                                                        class="payment-activation-button {{ $monthIsActive ? '' : 'is-inactive' }}"
                                                                        type="submit"
                                                                        title="{{ $monthIsActive ? 'Nonaktifkan tagihan' : 'Aktifkan tagihan' }}"
                                                                        aria-label="{{ $monthIsActive ? 'Nonaktifkan tagihan ' : 'Aktifkan tagihan ' }}{{ $bulan['periode_display'] ?? $bulan['periode'] }}"
                                                                    >
                                                                        <i class="fas {{ $monthIsActive ? 'fa-power-off' : 'fa-rotate-left' }}" aria-hidden="true"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                        @endforeach

                        @if($singleTagihanList->isNotEmpty())
                            <section class="payment-single-section" aria-labelledby="singleBillSectionTitle">
                                <header class="payment-single-section-title">
                                    <div class="payment-single-section-copy">
                                        <span class="payment-single-section-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                                        <div>
                                            <strong id="singleBillSectionTitle">Tagihan satuan</strong>
                                            <span>Tagihan sekali bayar tanpa rincian bulanan</span>
                                        </div>
                                    </div>
                                    <div class="payment-single-section-meta">
                                        <span class="payment-single-section-balance">
                                            <span>Sisa</span>
                                            <strong>{{ $formatRupiah($singleOutstandingTotal) }}</strong>
                                        </span>
                                        <span class="payment-single-count">{{ $singleTagihanList->count() }} tagihan</span>
                                    </div>
                                </header>
                                <div class="payment-single-table">
                                    <div class="payment-single-head payment-single-grid" aria-hidden="true">
                                        <span></span>
                                        <span><i class="fas fa-file-invoice payment-column-icon"></i>Tagihan</span>
                                        <span><i class="fas fa-calendar-days payment-column-icon"></i>Periode</span>
                                        <span><i class="fas fa-coins payment-column-icon"></i>Nominal</span>
                                        <span><i class="fas fa-wallet payment-column-icon"></i>Dibayar</span>
                                        <span><i class="fas fa-layer-group payment-column-icon"></i>Sisa</span>
                                        <span><i class="fas fa-signal payment-column-icon"></i>Status</span>
                                        <span>Aksi</span>
                                    </div>
                                    @foreach($singleTagihanList as $tagihan)
                                @php
                                    $billIsActive = (bool) ($tagihan['is_active'] ?? true);
                                    $billOutstanding = $billIsActive && (float) $tagihan['sisa_bayar'] > 0;
                                    $billDueOutstanding = (float) ($tagihan['sisa_terhitung'] ?? $tagihan['sisa_bayar']) > 0;
                                    $billNominal = (float) $tagihan['nominal'];
                                    $billPaid = max(0, $billNominal - (float) $tagihan['sisa_bayar']);
                                    [$billStatusLabel, $billStatusClass] = match($tagihan['status']) {
                                        'tidak_aktif' => ['Tidak aktif', 'is-inactive'],
                                        'lunas' => ['Lunas', 'is-paid'],
                                        'belum_jatuh_tempo' => ['Belum jatuh tempo', 'is-upcoming'],
                                        'sebagian' => ['Sebagian', 'is-partial'],
                                        default => ['Belum lunas', ''],
                                    };
                                    $billProgress = $billNominal > 0
                                        ? min(100, (int) round(($billPaid / $billNominal) * 100))
                                        : 0;
                                @endphp
                                <article class="payment-single payment-single-grid {{ $billIsActive ? '' : 'is-inactive' }}">
                                    <div class="payment-single-check">
                                        @if($billOutstanding)
                                            <input
                                                class="payment-check bill-checkbox"
                                                type="checkbox"
                                                value="{{ $tagihan['id'] }}"
                                                data-name="{{ $tagihan['nama_tagihan'] }}"
                                                data-nominal="{{ (float) $tagihan['sisa_bayar'] }}"
                                                data-periode=""
                                                data-is-spp="false"
                                            >
                                        @elseif($billIsActive)
                                            <i class="fas fa-check" style="color:#12a06a;font-size:9px"></i>
                                        @endif
                                    </div>
                                    <div class="payment-single-name">
                                        <strong>{{ $tagihan['nama_tagihan'] }}</strong>
                                        <div class="payment-single-subline">
                                            <span>{{ ucfirst($tagihan['tipe']) }}</span>
                                            <span class="payment-row-kind is-static">
                                                <i class="fas fa-file-lines" aria-hidden="true"></i>
                                                <span>Tanpa rincian</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="payment-single-period">
                                        <span class="payment-single-label">Periode</span>
                                        <div class="payment-single-value"><strong>{{ $tagihan['periode'] ?? '-' }}</strong></div>
                                    </div>
                                    <div class="payment-single-nominal">
                                        <span class="payment-single-label">Nominal</span>
                                        <div class="payment-single-value"><strong>{{ $formatRupiah($tagihan['nominal']) }}</strong></div>
                                    </div>
                                    <div class="payment-single-paid">
                                        <span class="payment-single-label">Dibayar</span>
                                        <span class="payment-paid-cell">
                                            <span class="payment-money">{{ $formatRupiah($billPaid) }}</span>
                                            <span class="pi-progress {{ $billProgress >= 100 ? 'is-success' : '' }}" role="progressbar" aria-label="Pembayaran {{ $billProgress }} persen" aria-valuenow="{{ $billProgress }}" aria-valuemin="0" aria-valuemax="100">
                                                <i style="width: {{ $billProgress }}%"></i>
                                            </span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="payment-single-label">Sisa</span>
                                        <div class="payment-single-value"><strong class="payment-money {{ $billDueOutstanding ? 'is-remaining' : '' }}">{{ $formatRupiah(max(0, $tagihan['sisa_terhitung'] ?? $tagihan['sisa_bayar'])) }}</strong></div>
                                    </div>
                                    <div>
                                        <span class="payment-single-label">Status</span>
                                        <span class="payment-status {{ $billStatusClass }}">{{ $billStatusLabel }}</span>
                                    </div>
                                    <div class="payment-single-action">
                                        <span class="payment-single-label">Aksi</span>
                                        @if($canGenerate)
                                            <form class="payment-activation-form" method="POST" action="{{ route('tagihan.toggle-active', $tagihan['id']) }}" data-confirm-title="Konfirmasi status tagihan" data-confirm-message="{{ $billIsActive ? 'Nonaktifkan tagihan ini? Nominalnya tidak akan dihitung.' : 'Aktifkan kembali tagihan ini?' }}" data-confirm-label="{{ $billIsActive ? 'Nonaktifkan' : 'Aktifkan' }}" data-confirm-tone="warning">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="payment-activation-button {{ $billIsActive ? '' : 'is-inactive' }}"
                                                    type="submit"
                                                    title="{{ $billIsActive ? 'Nonaktifkan tagihan' : 'Aktifkan tagihan' }}"
                                                    aria-label="{{ $billIsActive ? 'Nonaktifkan tagihan ' : 'Aktifkan tagihan ' }}{{ $tagihan['nama_tagihan'] }}"
                                                >
                                                    <i class="fas {{ $billIsActive ? 'fa-power-off' : 'fa-rotate-left' }}" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                @else
                    <div class="payment-empty">
                        <div>
                            <i class="fas fa-file-invoice"></i>
                            <strong>Belum ada tagihan</strong>
                            <span>Buat tagihan siswa agar pembayaran dapat diproses.</span>
                        </div>
                    </div>
                @endif
            </section>

            @if($canPay && count($tagihanList) > 0)
                <section class="payment-panel" id="paymentFormPanel" hidden>
                    <div class="payment-panel-heading">
                        <div class="payment-step-title">
                            <span class="payment-step-number">2</span>
                            <div>
                                <h2 class="payment-panel-title">Atur pembayaran</h2>
                                <p class="payment-panel-context">Jumlah dapat disesuaikan untuk pembayaran sebagian.</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('tagihan.proses.multi') }}" method="POST" id="multiPaymentForm">
                        @csrf
                        <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">

                        <div class="payment-form-body">
                            <div class="payment-selected-list" id="selectedBillsContainer"></div>

                            <div class="payment-details-grid">
                                <div class="payment-field">
                                    <label for="paymentDate">Tanggal bayar</label>
                                    <input id="paymentDate" type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', date('Y-m-d')) }}" required>
                                    @error('tanggal_bayar')
                                        <small class="payment-field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="payment-field">
                                    <label for="receiptDate">Tanggal kuitansi</label>
                                    <input id="receiptDate" type="date" name="tanggal_kwitansi" value="{{ old('tanggal_kwitansi', date('Y-m-d')) }}" required>
                                    @error('tanggal_kwitansi')
                                        <small class="payment-field-error">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="payment-field">
                                    <label for="paymentMethod">Metode pembayaran</label>
                                    <select id="paymentMethod" name="metode_bayar" required>
                                        <option value="tunai">Tunai</option>
                                        <option value="transfer">Transfer bank</option>
                                        <option value="kjc">KJC</option>
                                        <option value="tabungan">Potongan dari tabungan</option>
                                    </select>
                                </div>
                                <div class="payment-field is-full">
                                    <label for="paymentNote">Keterangan <span style="color:#98a2b3;font-weight:500">(opsional)</span></label>
                                    <textarea id="paymentNote" name="keterangan" placeholder="Tambahkan catatan pembayaran jika diperlukan..."></textarea>
                                </div>
                            </div>

                            <div class="payment-form-footer">
                                <div class="payment-form-total">
                                    <span>Total uang diterima</span>
                                    <strong id="paymentCashTotal">Rp0</strong>
                                </div>
                                <div class="payment-form-actions">
                                    <button class="payment-button" id="cancelPaymentButton" type="button">
                                        <i class="fas fa-xmark"></i>
                                        <span>Batal</span>
                                    </button>
                                    <button class="payment-button is-primary" id="submitPaymentButton" type="submit">
                                        <i class="fas fa-circle-check"></i>
                                        <span>Konfirmasi pembayaran</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
</div>

@if($canGenerate)
    <div class="modal fade" id="generateStudentModal" tabindex="-1" aria-labelledby="generateStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="generateStudentModalLabel">Buat tagihan siswa</h2>
                        <p class="payment-panel-context">{{ $siswa->nama }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <ul class="payment-modal-list">
                        <li><i class="fas fa-circle-check"></i><span>Membuat tagihan dari jenis pembayaran yang sesuai sekolah dan kelas.</span></li>
                        <li><i class="fas fa-circle-check"></i><span>SPP hanya dibuat jika sudah tercatat sebagai jenis pembayaran bulanan.</span></li>
                        <li><i class="fas fa-circle-check"></i><span>Tagihan yang sudah ada tidak akan digandakan.</span></li>
                    </ul>
                    <div class="payment-modal-note">
                        <i class="fas fa-circle-info"></i>
                        <span>Pastikan nominal, target pembayaran, dan penempatan kelas siswa sudah benar.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="payment-button" type="button" data-bs-dismiss="modal">Batal</button>
                    <form id="generateStudentForm" action="{{ route('tagihan.generate.manual.siswa', $siswa->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaran?->id }}">
                        <button class="payment-button is-primary" id="generateStudentButton" type="submit">
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
    const billCheckboxes = [...document.querySelectorAll('.bill-checkbox')];
    const selectAll = document.getElementById('selectAllBills');
    const groupChecks = [...document.querySelectorAll('[data-select-group]')];
    const selectedCount = document.getElementById('selectedBillCount');
    const selectedTotal = document.getElementById('selectedBillTotal');
    const continueButton = document.getElementById('continuePaymentButton');
    const selectionPanel = document.getElementById('billSelectionPanel');
    const formPanel = document.getElementById('paymentFormPanel');
    const selectedContainer = document.getElementById('selectedBillsContainer');
    const paymentDate = document.getElementById('paymentDate');
    const paymentForm = document.getElementById('multiPaymentForm');
    const submitButton = document.getElementById('submitPaymentButton');
    const sppDiscount = @json($sppDiscountConfig);

    const currency = (amount) => 'Rp' + Number(amount || 0).toLocaleString('id-ID');
    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    document.querySelectorAll('[data-group-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = document.getElementById(button.dataset.groupToggle);
            const group = button.closest('[data-payment-group]');
            const willOpen = panel.hidden;

            panel.hidden = !willOpen;
            group?.classList.toggle('is-open', willOpen);
            button.setAttribute('aria-expanded', String(willOpen));
        });
    });

    const updateGroupChecks = () => {
        groupChecks.forEach((groupCheck) => {
            const children = billCheckboxes.filter((checkbox) => checkbox.dataset.group === groupCheck.dataset.selectGroup);
            const checked = children.filter((checkbox) => checkbox.checked).length;

            groupCheck.checked = children.length > 0 && checked === children.length;
            groupCheck.indeterminate = checked > 0 && checked < children.length;
        });
    };

    const updateSelection = () => {
        const checked = billCheckboxes.filter((checkbox) => checkbox.checked);
        const total = checked.reduce((sum, checkbox) => sum + Number(checkbox.dataset.nominal || 0), 0);

        if (selectedCount) selectedCount.textContent = checked.length.toLocaleString('id-ID');
        if (selectedTotal) selectedTotal.textContent = currency(total);
        if (continueButton) continueButton.disabled = checked.length === 0;

        if (selectAll) {
            selectAll.checked = billCheckboxes.length > 0 && checked.length === billCheckboxes.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < billCheckboxes.length;
        }

        updateGroupChecks();
    };

    selectAll?.addEventListener('change', () => {
        billCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSelection();
    });

    groupChecks.forEach((groupCheck) => {
        groupCheck.addEventListener('change', () => {
            billCheckboxes
                .filter((checkbox) => checkbox.dataset.group === groupCheck.dataset.selectGroup)
                .forEach((checkbox) => {
                    checkbox.checked = groupCheck.checked;
                });
            updateSelection();
        });
    });

    billCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));

    const resetDiscount = (row) => {
        row.querySelector('[data-discount-amount]').value = '0';
        row.querySelector('[data-has-discount]').value = 'false';
    };

    const paymentAmount = (input) => {
        const value = input?.value ?? 0;
        return Number(window.PermataRupiah ? window.PermataRupiah.raw(value) : String(value).replace(/[^0-9]/g, '')) || 0;
    };

    const validateAmount = (input) => {
        const max = Number(input.dataset.maxAmount || 0);
        const value = paymentAmount(input);
        const error = input.closest('[data-payment-row]').querySelector('[data-payment-error]');
        const invalid = value < 0 || value > max;

        input.classList.toggle('is-invalid', invalid);
        error.classList.toggle('is-visible', invalid);
        error.textContent = value > max
            ? `Maksimal ${currency(max)}`
            : 'Jumlah tidak boleh kurang dari 0';

        return !invalid;
    };

    const updateCashTotal = () => {
        const total = [...document.querySelectorAll('.payment-amount-input')]
            .reduce((sum, input) => sum + paymentAmount(input), 0);
        const output = document.getElementById('paymentCashTotal');
        if (output) output.textContent = currency(total);
    };

    const recalculateRowDiscount = (row) => {
        const input = row.querySelector('.payment-amount-input');
        const info = row.querySelector('[data-discount-info]');
        const isSpp = row.dataset.isSpp === 'true';
        const period = row.dataset.period;
        const billDeadline = row.dataset.deadline;
        const original = Number(row.dataset.originalAmount || 0);

        resetDiscount(row);
        info.className = 'payment-discount-info';
        info.innerHTML = '';

        if (!isSpp || !sppDiscount.active || Number(sppDiscount.amount) <= 0 || !paymentDate?.value) {
            validateAmount(input);
            updateCashTotal();
            return;
        }

        const paidAt = new Date(paymentDate.value + 'T00:00:00');
        let isWithinPeriod = false;
        let periodMessage = '';

        if (sppDiscount.custom_period) {
            const validFrom = new Date(sppDiscount.valid_from + 'T00:00:00');
            const validUntil = new Date(sppDiscount.valid_until + 'T00:00:00');
            isWithinPeriod = paidAt >= validFrom && paidAt <= validUntil;
            periodMessage = `pada periode ${validFrom.toLocaleDateString('id-ID')}–${validUntil.toLocaleDateString('id-ID')}`;
        } else if (billDeadline || period) {
            const deadline = billDeadline
                ? new Date(billDeadline + 'T00:00:00')
                : new Date(period + '-10T00:00:00');
            isWithinPeriod = paidAt <= deadline;
            periodMessage = `sampai ${deadline.toLocaleDateString('id-ID')}`;
        }

        const discount = Math.min(Number(sppDiscount.amount), original);
        const discountedAmount = Math.max(0, original - discount);
        const amount = paymentAmount(input);

        if (isWithinPeriod && (amount === original || amount === discountedAmount)) {
            input.value = discountedAmount;
            window.PermataRupiah?.refresh(input);
            row.querySelector('[data-discount-amount]').value = discount;
            row.querySelector('[data-has-discount]').value = 'true';
            info.classList.add('is-visible');
            info.innerHTML = `<i class="fas fa-tag"></i><span>Diskon ${currency(discount)} diterapkan. Tagihan lunas dengan pembayaran ${currency(discountedAmount)}.</span>`;
        } else if (isWithinPeriod) {
            info.classList.add('is-visible', 'is-warning');
            info.innerHTML = `<i class="fas fa-circle-info"></i><span>Diskon ${currency(discount)} tersedia untuk pelunasan penuh ${periodMessage}.</span>`;
        }

        validateAmount(input);
        updateCashTotal();
    };

    const recalculateAllDiscounts = () => {
        document.querySelectorAll('[data-payment-row]').forEach(recalculateRowDiscount);
    };

    const renderSelectedBills = () => {
        const checked = billCheckboxes.filter((checkbox) => checkbox.checked);

        selectedContainer.innerHTML = checked.map((checkbox) => {
            const id = checkbox.value;
            const amount = Number(checkbox.dataset.nominal || 0);

            return `
                <article class="payment-selected-item" data-payment-row data-bill-id="${id}" data-is-spp="${checkbox.dataset.isSpp}" data-period="${escapeHtml(checkbox.dataset.periode || '')}" data-deadline="${escapeHtml(checkbox.dataset.deadline || '')}" data-original-amount="${amount}">
                    <div class="payment-selected-main">
                        <span class="payment-selected-icon"><i class="fas fa-file-invoice"></i></span>
                        <span class="payment-selected-copy">
                            <strong>${escapeHtml(checkbox.dataset.name)}</strong>
                            <span>Sisa tagihan ${currency(amount)}</span>
                        </span>
                    </div>
                    <div class="payment-selected-actions">
                        <div class="payment-amount-field">
                            <label for="payment-amount-${id}">Jumlah bayar</label>
                            <input id="payment-amount-${id}" class="payment-amount-input" type="number" name="pembayaran[${id}]" value="${amount}" min="0" max="${amount}" data-max-amount="${amount}" data-rupiah required>
                            <span class="payment-input-error" data-payment-error></span>
                        </div>
                        <button class="payment-quick-button" type="button" data-pay-full>
                            <i class="fas fa-check"></i><span>Lunasi</span>
                        </button>
                    </div>
                    <div class="payment-discount-info" data-discount-info></div>
                    <input type="hidden" name="original_amount[${id}]" value="${amount}">
                    <input type="hidden" name="discount_amount[${id}]" value="0" data-discount-amount>
                    <input type="hidden" name="has_discount[${id}]" value="false" data-has-discount>
                </article>
            `;
        }).join('');

        selectedContainer.querySelectorAll('.payment-amount-input').forEach((input) => {
            input.addEventListener('input', () => recalculateRowDiscount(input.closest('[data-payment-row]')));
        });

        selectedContainer.querySelectorAll('[data-pay-full]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = button.closest('[data-payment-row]');
                const input = row.querySelector('.payment-amount-input');
                input.value = row.dataset.originalAmount;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        recalculateAllDiscounts();
    };

    continueButton?.addEventListener('click', () => {
        renderSelectedBills();
        formPanel.hidden = false;
        formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.getElementById('cancelPaymentButton')?.addEventListener('click', () => {
        formPanel.hidden = true;
        selectionPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    paymentDate?.addEventListener('change', recalculateAllDiscounts);

    paymentForm?.addEventListener('submit', async (event) => {
        const inputs = [...paymentForm.querySelectorAll('.payment-amount-input')];
        const valid = inputs.every(validateAmount);
        const total = inputs.reduce((sum, input) => sum + paymentAmount(input), 0);

        if (!valid || total <= 0) {
            event.preventDefault();
            window.alert(valid ? 'Jumlah pembayaran harus lebih dari Rp0.' : 'Periksa kembali jumlah pembayaran.');
            return;
        }

        if (paymentForm.dataset.paymentConfirmed === 'true') {
            delete paymentForm.dataset.paymentConfirmed;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Memproses...</span>';
            return;
        }

        event.preventDefault();
        const confirmed = await window.appConfirm({
            title: 'Konfirmasi pembayaran',
            message: `Pastikan data pembayaran sudah benar. Total yang akan diterima sebesar ${currency(total)}.`,
            confirmText: 'Proses pembayaran',
            tone: 'success',
        });
        if (!confirmed) return;

        paymentForm.dataset.paymentConfirmed = 'true';
        paymentForm.requestSubmit(submitButton);
    });

    const generateForm = document.getElementById('generateStudentForm');
    const generateButton = document.getElementById('generateStudentButton');
    generateForm?.addEventListener('submit', () => {
        generateButton.disabled = true;
        generateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sedang membuat...</span>';
    });

    updateSelection();
});
</script>
@endsection
