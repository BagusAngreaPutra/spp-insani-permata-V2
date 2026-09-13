@php
    $student = $receipt['student'] ?? null;
    $school = $receipt['school'] ?? $student?->sekolah;
    $items = collect($receipt['items'] ?? []);
    $paidTotal = (float) ($receipt['total_paid'] ?? $items->sum('amount'));
    $discountTotal = (float) ($receipt['total_discount'] ?? $items->sum('discount'));
    $transactionTotal = $paidTotal + $discountTotal;
    $paymentDate = $receipt['payment_date'] ?? $receipt['date'] ?? now();
    $receiptDate = $receipt['receipt_date'] ?? $paymentDate;
    $paidAt = \Carbon\Carbon::parse($paymentDate);
    $receiptAt = \Carbon\Carbon::parse($receiptDate);
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
    $logoPath = public_path('images/logo.jpg');
    $logoData = is_file($logoPath)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
    $documentLabel = $receipt['document_label'] ?? ($items->count() > 1 ? 'Kwitansi gabungan' : 'Kwitansi pembayaran');
    $itemNoun = $receipt['item_noun'] ?? 'tagihan';
    $systemLabel = $receipt['system_label'] ?? 'Sistem Pembayaran Permata Insani';
@endphp

<style>
    .receipt-document, .receipt-document * { box-sizing: border-box; }
    .receipt-document { width: 100%; color: #1f2937; font-family: Arial, Helvetica, sans-serif; font-size: 8px; line-height: 1.35; }
    .receipt-document table { width: 100%; border-collapse: collapse; }
    .receipt-document-header { border-bottom: 1.5px solid #111827; }
    .receipt-document-header td { padding: 0 0 4.5mm; vertical-align: top; }
    .receipt-brand-cell { width: 67%; }
    .receipt-brand-table { width: 100%; }
    .receipt-brand-table td { padding-bottom: 0; }
    .receipt-logo-cell { width: 13mm; padding-right: 3mm !important; }
    .receipt-logo { display: block; width: 10mm; height: 10mm; object-fit: contain; filter: grayscale(1); opacity: .82; }
    .receipt-brand-copy strong, .receipt-brand-copy span { display: block; }
    .receipt-brand-copy strong { color: #111827; font-size: 10px; line-height: 1.2; }
    .receipt-brand-copy span { margin-top: 1mm; color: #667085; font-size: 6.8px; line-height: 1.4; }
    .receipt-id-cell { width: 33%; text-align: right; }
    .receipt-label { display: block; color: #667085; font-size: 6.3px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .receipt-number { display: block; margin-top: 1.4mm; color: #111827; font-size: 10px; line-height: 1.2; overflow-wrap: anywhere; }
    .receipt-state { display: block; margin-top: 1.5mm; color: #087451; font-size: 6.4px; font-weight: 700; text-transform: uppercase; }
    .receipt-meta { margin: 4mm 0; border-top: .3mm solid #d0d5dd; border-bottom: .3mm solid #d0d5dd; }
    .receipt-meta td { width: 25%; padding: 2.7mm 2.5mm; vertical-align: top; }
    .receipt-meta td:first-child { padding-left: 0; }
    .receipt-meta td + td { border-left: .3mm solid #eaecf0; }
    .receipt-value { display: block; margin-top: 1mm; color: #344054; font-size: 7.3px; font-weight: 700; overflow-wrap: anywhere; }
    .receipt-recipient { margin-bottom: 4mm; border-bottom: .3mm solid #d0d5dd; }
    .receipt-recipient td { width: 50%; padding: 0 0 3.5mm; vertical-align: top; }
    .receipt-recipient td + td { padding-left: 4mm; border-left: .3mm solid #eaecf0; }
    .receipt-recipient .receipt-value { color: #111827; font-size: 8px; }
    .receipt-recipient small { display: block; margin-top: 1mm; color: #667085; font-size: 6.7px; }
    .receipt-section-heading { margin-bottom: 1.8mm; }
    .receipt-section-heading strong { color: #111827; font-size: 8px; }
    .receipt-section-heading span { float: right; color: #667085; font-size: 6.5px; }
    .receipt-items { table-layout: fixed; border-top: .4mm solid #98a2b3; border-bottom: .4mm solid #98a2b3; }
    .receipt-items th { padding: 2.2mm 1.4mm; color: #475467; border-bottom: .3mm solid #d0d5dd; font-size: 6.3px; text-align: left; text-transform: uppercase; }
    .receipt-items td { padding: 2.5mm 1.4mm; color: #344054; border-bottom: .3mm solid #eaecf0; font-size: 7px; vertical-align: middle; overflow-wrap: anywhere; }
    .receipt-items tbody tr:last-child td { border-bottom: 0; }
    .receipt-col-number { width: 7%; text-align: center !important; }
    .receipt-col-description { width: 45%; }
    .receipt-col-money { width: 16%; text-align: right !important; white-space: nowrap; }
    .receipt-item-meta { display: block; margin-top: .8mm; color: #667085; font-size: 6.2px; font-weight: 400; }
    .receipt-totals { margin-top: 3mm; }
    .receipt-totals td { vertical-align: top; }
    .receipt-spelled { width: 51%; padding: 1.5mm 4mm 1.5mm 0; }
    .receipt-spelled strong { display: block; margin-top: 1mm; color: #344054; font-size: 7px; line-height: 1.5; }
    .receipt-summary { width: 49%; padding-left: 4mm; border-left: .3mm solid #d0d5dd; }
    .receipt-summary-row { min-height: 5mm; padding: 1mm 0; color: #667085; font-size: 6.8px; }
    .receipt-summary-row strong { float: right; color: #344054; white-space: nowrap; }
    .receipt-summary-row.is-total { margin-top: 1mm; padding-top: 2mm; color: #111827; border-top: .3mm solid #d0d5dd; font-size: 7.5px; font-weight: 700; }
    .receipt-summary-row.is-total strong { color: #111827; font-size: 9px; }
    .receipt-note { margin-top: 3mm; padding: 2.5mm 0; color: #475467; border-top: .3mm solid #d0d5dd; border-bottom: .3mm solid #d0d5dd; font-size: 6.8px; }
    .receipt-note strong { display: block; margin-bottom: .8mm; color: #344054; }
    .receipt-signatures { margin-top: 5mm; border-top: .3mm solid #eaecf0; }
    .receipt-signatures td { width: 50%; padding-top: 4mm; vertical-align: bottom; }
    .receipt-validity strong { display: block; color: #344054; font-size: 7px; }
    .receipt-validity span { display: block; margin-top: 1mm; color: #667085; font-size: 6.2px; line-height: 1.45; }
    .receipt-signature { color: #475467; font-size: 6.8px; text-align: center; }
    .receipt-signature-line { display: inline-block; width: 34mm; margin-top: 10mm; padding-top: 1.5mm; color: #111827; border-top: .3mm solid #667085; font-weight: 700; }
    .receipt-footer { margin-top: 4mm; padding-top: 2mm; color: #667085; border-top: .3mm dashed #d0d5dd; font-size: 5.8px; }
    .receipt-footer span:last-child { float: right; }
    .receipt-items tr, .receipt-totals, .receipt-note, .receipt-signatures { page-break-inside: avoid; }

    /* Tipografi kwitansi: satu font dan rentang ukuran yang rapat seperti struk umum. */
    [data-receipt-document].receipt-document,
    [data-receipt-document].receipt-document * {
        font-family: Arial, Helvetica, sans-serif !important;
    }
    [data-receipt-document].receipt-document {
        font-size: 10px !important;
        line-height: 1.4 !important;
    }
    [data-receipt-document].receipt-document table th,
    [data-receipt-document].receipt-document table td {
        font-family: inherit !important;
        line-height: 1.4 !important;
    }
    [data-receipt-document].receipt-document .receipt-brand-copy strong { font-size: 11px !important; }
    [data-receipt-document].receipt-document .receipt-brand-copy span { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-label { font-size: 8.5px !important; }
    [data-receipt-document].receipt-document .receipt-number { font-size: 10.5px !important; }
    [data-receipt-document].receipt-document .receipt-state { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-value { font-size: 9.5px !important; }
    [data-receipt-document].receipt-document .receipt-recipient .receipt-value { font-size: 10px !important; }
    [data-receipt-document].receipt-document .receipt-recipient small { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-section-heading strong { font-size: 9.5px !important; }
    [data-receipt-document].receipt-document .receipt-section-heading span { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-items th { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-items td { font-size: 10px !important; }
    [data-receipt-document].receipt-document .receipt-item-meta { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-spelled strong { font-size: 9.5px !important; }
    [data-receipt-document].receipt-document .receipt-summary-row { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-summary-row.is-total,
    [data-receipt-document].receipt-document .receipt-summary-row.is-total strong { font-size: 10px !important; }
    [data-receipt-document].receipt-document .receipt-note { font-size: 9.5px !important; }
    [data-receipt-document].receipt-document .receipt-validity strong,
    [data-receipt-document].receipt-document .receipt-signature { font-size: 9.5px !important; }
    [data-receipt-document].receipt-document .receipt-validity span { font-size: 9px !important; }
    [data-receipt-document].receipt-document .receipt-signature-line { font-size: 10px !important; }
    [data-receipt-document].receipt-document .receipt-footer { font-size: 8.5px !important; }

</style>

<article class="receipt-document {{ ($clientPdfFallback ?? false) ? 'is-client-pdf-export' : '' }}" data-receipt-document>
    <table class="receipt-document-header"><tr>
        <td class="receipt-brand-cell">
            <table class="receipt-brand-table"><tr>
                @if($logoData)<td class="receipt-logo-cell"><img class="receipt-logo" src="{{ $logoData }}" alt="Logo Permata Insani"></td>@endif
                <td class="receipt-brand-copy">
                    <strong>Yayasan Kemilau Permata Insani</strong>
                    <span>{{ $school?->nama_sekolah ?? 'Permata Insani Islamic School' }}</span>
                    <span>{{ $school?->alamat ?? 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi' }}</span>
                </td>
            </tr></table>
        </td>
        <td class="receipt-id-cell">
            <span class="receipt-label">{{ $documentLabel }}</span>
            <strong class="receipt-number">{{ $receipt['number'] ?? '-' }}</strong>
            <span class="receipt-state">Pembayaran diterima</span>
        </td>
    </tr></table>

    <table class="receipt-meta"><tr>
        <td><span class="receipt-label">Tanggal transaksi</span><strong class="receipt-value">{{ $paidAt->locale('id')->translatedFormat('d F Y') }}</strong></td>
        <td><span class="receipt-label">Tanggal kuitansi</span><strong class="receipt-value">{{ $receiptAt->locale('id')->translatedFormat('d F Y') }}</strong></td>
        <td><span class="receipt-label">Metode</span><strong class="receipt-value">{{ $receipt['method'] ?? '-' }}</strong></td>
        <td><span class="receipt-label">Jumlah item</span><strong class="receipt-value">{{ $items->count() }} {{ $itemNoun }}</strong></td>
    </tr></table>

    <table class="receipt-recipient"><tr>
        <td><span class="receipt-label">Diterima dari</span><strong class="receipt-value">{{ $student?->nama ?? 'Nama siswa tidak tersedia' }}</strong><small>NIS {{ $student?->nis ?? '-' }} · {{ $classLabel }}</small></td>
        <td><span class="receipt-label">Unit sekolah</span><strong class="receipt-value">{{ $school?->nama_sekolah ?? 'Sekolah belum diatur' }}</strong><small>{{ $school?->kode_sekolah ? 'Kode sekolah ' . $school->kode_sekolah : 'Kode sekolah belum diatur' }}</small></td>
    </tr></table>

    <div class="receipt-section-heading"><strong>Rincian transaksi</strong><span>{{ $items->count() }} item</span></div>
    <table class="receipt-items">
        <thead><tr><th class="receipt-col-number">No.</th><th class="receipt-col-description">Rincian pembayaran</th><th class="receipt-col-money">Nilai</th><th class="receipt-col-money">Potongan</th><th class="receipt-col-money">Diterima</th></tr></thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $itemAmount = (float) ($item['amount'] ?? 0);
                    $itemDiscount = (float) ($item['discount'] ?? 0);
                @endphp
                <tr>
                    <td class="receipt-col-number">{{ $loop->iteration }}</td>
                    <td class="receipt-col-description"><strong>{{ $item['name'] ?? '-' }}</strong>@if(!empty($item['meta']))<span class="receipt-item-meta">{{ $item['meta'] }}</span>@endif</td>
                    <td class="receipt-col-money">{{ $formatRupiah($itemAmount + $itemDiscount) }}</td>
                    <td class="receipt-col-money">{{ $itemDiscount > 0 ? '- ' . $formatRupiah($itemDiscount) : '—' }}</td>
                    <td class="receipt-col-money"><strong>{{ $formatRupiah($itemAmount) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="receipt-totals"><tr>
        <td class="receipt-spelled"><span class="receipt-label">Terbilang</span><strong>{{ $spelledAmount }}</strong></td>
        <td class="receipt-summary">
            <div class="receipt-summary-row">Nilai transaksi <strong>{{ $formatRupiah($transactionTotal) }}</strong></div>
            @if($discountTotal > 0)<div class="receipt-summary-row">Total potongan <strong>- {{ $formatRupiah($discountTotal) }}</strong></div>@endif
            <div class="receipt-summary-row is-total">Jumlah diterima <strong>{{ $formatRupiah($paidTotal) }}</strong></div>
        </td>
    </tr></table>

    @if($showNote)<div class="receipt-note"><strong>Keterangan</strong>{{ $rawNote }}</div>@endif

    <table class="receipt-signatures"><tr>
        <td class="receipt-validity"><strong>Bukti pembayaran resmi</strong><span>Kwitansi ini dibuat oleh {{ $systemLabel }} dan sah tanpa stempel tambahan.</span></td>
        <td class="receipt-signature">Jambi, {{ $receiptAt->locale('id')->translatedFormat('d F Y') }}<br><span class="receipt-signature-line">Petugas administrasi</span></td>
    </tr></table>

    <div class="receipt-footer"><span>Dicetak melalui {{ $systemLabel }}.</span><span>No. {{ $receipt['number'] ?? '-' }}</span></div>
</article>
