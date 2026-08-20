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
    $logoPath = public_path('images/logo.jpg');
    $logoData = is_file($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;
@endphp

<style>
    @page { size: A4 portrait; margin: 12mm; }

    #receipt-document {
        width: 186mm;
        max-width: 100%;
        min-width: 0;
        margin: 0 auto;
        overflow: hidden;
        color: #181818;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        line-height: 1.45;
    }

    #receipt-document table { border-collapse: collapse; }

    #receipt-document .receipt-document-header,
    #receipt-document .receipt-meta,
    #receipt-document .receipt-recipient,
    #receipt-document .receipt-totals-area,
    #receipt-document .receipt-signature-grid {
        display: table !important;
        width: 100% !important;
    }

    #receipt-document .receipt-document-header { border-bottom: 2px solid #181818; }
    #receipt-document .receipt-document-header > tbody > tr > td { padding-bottom: 22px; vertical-align: top; }
    #receipt-document .receipt-brand { display: table-cell !important; width: 64%; }
    #receipt-document .receipt-document-id { display: table-cell !important; width: 36%; text-align: right; }
    #receipt-document .receipt-brand-table { display: table !important; width: 100% !important; }
    #receipt-document .receipt-logo-cell { padding-right: 12px; vertical-align: top; width: 60px; }
    #receipt-document .receipt-logo { height: 48px; opacity: .82; width: 48px; filter: grayscale(1); }
    #receipt-document .receipt-brand-copy { vertical-align: top; }
    #receipt-document .receipt-brand-copy strong,
    #receipt-document .receipt-brand-copy span { display: block; }
    #receipt-document .receipt-brand-copy strong { color: #181818; font-size: 15px; font-weight: 700; line-height: 1.25; }
    #receipt-document .receipt-brand-copy span { color: #666; font-size: 10px; line-height: 1.45; margin-top: 3px; }
    #receipt-document .receipt-document-label { color: #666; font-size: 8.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    #receipt-document .receipt-number { color: #181818; display: block; font-size: 15px; font-weight: 700; line-height: 1.25; margin-top: 5px; overflow-wrap: anywhere; }
    #receipt-document .receipt-paid-status { color: #555; display: block; font-size: 8.5px; font-weight: 600; letter-spacing: .04em; margin-top: 7px; text-transform: uppercase; }

    #receipt-document .receipt-meta { border-bottom: 1px solid #d7d7d7; border-top: 1px solid #d7d7d7; margin: 18px 0; }
    #receipt-document .receipt-meta td { padding: 12px 16px; vertical-align: top; width: 25%; }
    #receipt-document .receipt-meta td:first-child { padding-left: 0; }
    #receipt-document .receipt-meta td + td { border-left: 1px solid #e2e2e2; }
    #receipt-document .receipt-label { color: #777; display: block; font-size: 8px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
    #receipt-document .receipt-value { color: #333; display: block; font-size: 11px; font-weight: 650; margin-top: 4px; }

    #receipt-document .receipt-recipient { border-bottom: 1px solid #d7d7d7; margin-bottom: 20px; }
    #receipt-document .receipt-recipient td { padding: 0 0 16px; vertical-align: top; width: 50%; }
    #receipt-document .receipt-recipient td + td { border-left: 1px solid #e4e7ec; padding-left: 20px; }
    #receipt-document .receipt-recipient .receipt-value { color: #181818; font-size: 12px; font-weight: 700; margin-top: 5px; }
    #receipt-document .receipt-recipient small { color: #666; display: block; font-size: 9.5px; line-height: 1.45; margin-top: 3px; }

    #receipt-document .receipt-section-heading { display: block !important; margin-bottom: 8px; width: 100%; }
    #receipt-document .receipt-section-heading strong { color: #181818; font-size: 11.5px; font-weight: 700; }
    #receipt-document .receipt-section-heading span { color: #4a4a4a; float: right; font-size: 8.5px; font-weight: 650; }
    #receipt-document .receipt-table { border-bottom: 1px dashed #777 !important; border-top: 1px dashed #777 !important; display: table !important; min-width: 0 !important; table-layout: fixed; width: 100% !important; }
    #receipt-document .receipt-table th { border-bottom: 1px dashed #777 !important; color: #555; font-size: 8.5px; font-weight: 700; height: 36px; letter-spacing: .02em; padding: 8px 10px; text-align: left; text-transform: uppercase; }
    #receipt-document .receipt-table td { border-bottom: 1px dashed #b0b0b0 !important; color: #333; font-size: 10px; height: 44px; padding: 9px 10px; vertical-align: middle; }
    #receipt-document .receipt-table tbody tr:last-child td { border-bottom: 0 !important; }
    #receipt-document .receipt-table-number { text-align: center !important; width: 42px; }
    #receipt-document .receipt-table-description { overflow-wrap: anywhere; width: auto; }
    #receipt-document .receipt-table-description strong { font-weight: 600; }
    #receipt-document .receipt-table-amount { text-align: right !important; white-space: nowrap; width: 126px; }

    #receipt-document .receipt-totals-area { margin-top: 17px; }
    #receipt-document .receipt-spelled { color: #4a4a4a; padding: 10px 18px 10px 0; vertical-align: top; width: 50%; }
    #receipt-document .receipt-spelled strong { color: #333; display: block; font-size: 10px; font-weight: 600; line-height: 1.55; margin-top: 5px; }
    #receipt-document .receipt-summary { border-left: 1px solid #d7d7d7; padding-left: 18px; vertical-align: top; width: 50%; }
    #receipt-document .receipt-summary-row { color: #666; font-size: 9.5px; min-height: 27px; padding: 4px 0; }
    #receipt-document .receipt-summary-row strong { color: #333; float: right; font-weight: 650; white-space: nowrap; }
    #receipt-document .receipt-summary-row.is-total { border-top: 1px solid #d0d5dd; color: #181818; font-size: 10.5px; font-weight: 700; margin-top: 6px; padding-top: 10px; }
    #receipt-document .receipt-summary-row.is-total strong { color: #181818; font-size: 14px; font-weight: 750; }
    #receipt-document .receipt-note { border-bottom: 1px solid #d7d7d7; border-top: 1px solid #d7d7d7; color: #4a4a4a; font-size: 9.5px; line-height: 1.5; margin-top: 14px; padding: 10px 0; }
    #receipt-document .receipt-note strong { color: #333; display: block; font-size: 9px; }

    #receipt-document .receipt-signature-grid { border-top: 1px solid #e4e7ec; margin-top: 28px; }
    #receipt-document .receipt-signature-grid td { padding-top: 22px; vertical-align: bottom; width: 50%; }
    #receipt-document .receipt-validity strong { color: #333; display: block; font-size: 9.5px; font-weight: 700; }
    #receipt-document .receipt-validity span { color: #777; display: block; font-size: 8.5px; line-height: 1.5; margin-top: 3px; }
    #receipt-document .receipt-signature { color: #4a4a4a; font-size: 9.5px; text-align: center; }
    #receipt-document .receipt-signature-line { border-top: 1px solid #666; color: #181818; display: inline-block; font-weight: 650; margin-top: 48px; padding-top: 7px; width: 190px; }
    #receipt-document .receipt-document-footer { border-top: 1px dashed #d0d5dd; color: #777; font-size: 7.5px; line-height: 1.4; margin-top: 21px; padding-top: 10px; }
    #receipt-document .receipt-document-footer span:last-child { float: right; }

    @media print {
        html, body {
            background: #fff !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            width: auto !important;
        }

        .receipt-paper {
            margin: 0 !important;
            padding: 0 !important;
            width: 186mm !important;
        }

        #receipt-document {
            margin: 0 !important;
            padding: 0 !important;
            width: 186mm !important;
        }
    }
</style>

<div id="receipt-document">
    <table class="receipt-document-header">
        <tr>
            <td class="receipt-brand">
                <table class="receipt-brand-table"><tr>
                    @if($logoData)<td class="receipt-logo-cell"><img class="receipt-logo" src="{{ $logoData }}" alt="Logo Permata Insani"></td>@endif
                    <td class="receipt-brand-copy">
                        <strong>Yayasan Kemilau Permata Insani</strong>
                        <span>{{ $school?->nama_sekolah ?? 'Permata Insani Islamic School' }}</span>
                        <span>{{ $school?->alamat ?? 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi' }}</span>
                    </td>
                </tr></table>
            </td>
            <td class="receipt-document-id">
                <span class="receipt-document-label">{{ $isGrouped ? 'Kwitansi gabungan' : 'Kwitansi pembayaran' }}</span>
                <strong class="receipt-number">{{ $receipt['number'] }}</strong>
                <span class="receipt-paid-status">Pembayaran diterima</span>
            </td>
        </tr>
    </table>

    <table class="receipt-meta"><tr>
        <td><span class="receipt-label">Tanggal pembayaran</span><strong class="receipt-value">{{ $paidAt->translatedFormat('d F Y') }}</strong></td>
        <td><span class="receipt-label">Tanggal kuitansi</span><strong class="receipt-value">{{ $receiptAt->translatedFormat('d F Y') }}</strong></td>
        <td><span class="receipt-label">Metode pembayaran</span><strong class="receipt-value">{{ $receipt['method'] }}</strong></td>
        <td><span class="receipt-label">Jumlah item</span><strong class="receipt-value">{{ $items->count() }} tagihan</strong></td>
    </tr></table>

    <table class="receipt-recipient"><tr>
        <td><span class="receipt-label">Diterima dari</span><strong class="receipt-value">{{ $student?->nama ?? 'Nama siswa tidak tersedia' }}</strong><small>NIS {{ $student?->nis ?? '-' }} · {{ $classLabel }}</small></td>
        <td><span class="receipt-label">Unit sekolah</span><strong class="receipt-value">{{ $school?->nama_sekolah ?? 'Sekolah belum diatur' }}</strong><small>{{ $school?->kode_sekolah ? 'Kode sekolah ' . $school->kode_sekolah : 'Kode sekolah belum diatur' }}</small></td>
    </tr></table>

    <div class="receipt-section-heading"><strong>Rincian transaksi</strong><span>{{ $items->count() }} item</span></div>
    <table class="receipt-table">
        <thead><tr><th class="receipt-table-number">No.</th><th class="receipt-table-description">Rincian pembayaran</th><th class="receipt-table-amount">Nilai transaksi</th><th class="receipt-table-amount">Potongan</th><th class="receipt-table-amount">Diterima</th></tr></thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="receipt-table-number">{{ $loop->iteration }}</td>
                    <td class="receipt-table-description"><strong>{{ $item['name'] }}</strong></td>
                    <td class="receipt-table-amount">{{ $formatRupiah($item['amount'] + $item['discount']) }}</td>
                    <td class="receipt-table-amount">{{ $item['discount'] > 0 ? '- ' . $formatRupiah($item['discount']) : '—' }}</td>
                    <td class="receipt-table-amount"><strong>{{ $formatRupiah($item['amount']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="receipt-totals-area"><tr>
        <td class="receipt-spelled"><span class="receipt-label">Terbilang</span><strong>{{ $spelledAmount }}</strong></td>
        <td class="receipt-summary">
            <div class="receipt-summary-row">Nilai transaksi <strong>{{ $formatRupiah($transactionTotal) }}</strong></div>
            @if($discountTotal > 0)<div class="receipt-summary-row">Total potongan <strong>- {{ $formatRupiah($discountTotal) }}</strong></div>@endif
            <div class="receipt-summary-row is-total">Jumlah diterima <strong>{{ $formatRupiah($paidTotal) }}</strong></div>
        </td>
    </tr></table>

    @if($showNote)<div class="receipt-note"><strong>Keterangan</strong>{{ $rawNote }}</div>@endif

    <table class="receipt-signature-grid"><tr>
        <td class="receipt-validity"><strong>Bukti pembayaran resmi</strong><span>Kwitansi ini dibuat oleh Sistem Pembayaran Permata Insani dan sah tanpa stempel tambahan.</span></td>
        <td class="receipt-signature">Jambi, {{ $receiptAt->translatedFormat('d F Y') }}<br><span class="receipt-signature-line">Petugas administrasi</span></td>
    </tr></table>

    <div class="receipt-document-footer"><span>Dokumen dicetak melalui Sistem Pembayaran Permata Insani.</span><span>No. {{ $receipt['number'] }}</span></div>
</div>
