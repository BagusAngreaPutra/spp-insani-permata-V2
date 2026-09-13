@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
    /* ====== STYLE NORMAL ====== */
    .main-content {
        margin-left: 280px;
        min-height: 100vh;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        position: absolute;
        right: 0;
        top: 0;
        width: calc(100% - 280px);
    }
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            position: relative;
        }
    }
    .content-area {
        padding: 3rem 2.5rem;
    }
    .table-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2.5rem;
    }
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }
    .modern-table th,
    .modern-table td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(220, 252, 231, 0.8);
        text-align: left;
    }
    .modern-table th {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        font-weight: 700;
        color: #166534;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .modern-table tbody tr:hover {
        background: rgba(34, 197, 94, 0.05);
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        padding: 2rem;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    .page-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #2d3748, #4a5568);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
    }
    
    .filter-section {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #22c55e;
        outline: none;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
        box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
    }
    
    .warning-message {
        display: none;
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 12px;
        padding: 1rem;
        margin-top: 1rem;
        color: #92400e;
    }
    
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .badge-success {
        background: linear-gradient(135deg, #bbf7d0, #86efac);
        color: #166534;
    }
    
    .badge-info {
        background: linear-gradient(135deg, #cbe8da, #a7d8bd);
        color: #15533b;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #d1d5db;
    }
    
    .table-header {
        padding: 1.5rem 2rem 0;
    }
    
    .table-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    /* Subtotal styles */
    .subtotal-section {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }
    
    .subtotal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
    }
    
    .subtotal-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .subtotal-item {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
    }
    
    .subtotal-label {
        font-weight: 600;
        color: #166534;
        margin-bottom: 0.5rem;
    }
    
    .subtotal-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #166534;
    }
    
    /* ====== STYLE KHUSUS CETAK ====== */
    @media print {
@page{
            size: landscape;
        }
        aside.sidebar,
        .page-header,
        .btn-primary,
        .btn-print,
        nav,
        header,
        .sidebar,
        .filter-section {
            display: none !important;
        }
        
        body, .main-content {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #fff !important;
        }
        
        .content-area {
            padding: 20px;
        }
        
        .table-container {
            background: none;
            box-shadow: none;
            border: none;
            border-radius: 0;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .modern-table th,
        .modern-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .modern-table th {
            background: #f0f0f0;
            color: #000;
            font-weight: bold;
            text-transform: none;
            letter-spacing: normal;
        }
        
        h3.print-title {
            text-align: center;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        
        .kop-laporan {
            text-align: center;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        
        .kop-laporan h2 {
            margin: 0;
            font-size: 22px;
        }
        
        .kop-laporan p {
            margin: 2px 0;
            font-size: 14px;
        }
        
        hr.print-hr {
            border: 1px solid #000;
            margin: 20px 0;
        }
        
        .tanggal-laporan {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .print-info {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Adjustments for better print fit - Reduced margins */
        @page {
            margin: 5mm;
        }
        
        body {
            margin: 0;
            padding: 5mm;
        }
        
        .modern-table {
            font-size: 10px;
        }
        
        .modern-table th,
        .modern-table td {
            padding: 3px 5px;
        }
        
        .print-footer {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            margin: 60px 0 10px 0;
            border-top: 1px solid #000;
        }
        
        /* Print subtotal styles */
        .subtotal-section {
            background: none;
            box-shadow: none;
            border: 1px solid #000;
            border-radius: 0;
            padding: 10px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .subtotal-title {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .subtotal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 5px;
        }
        
        .subtotal-item {
            background: #f0f0f0;
            border-radius: 0;
            padding: 5px;
        }
        
        .subtotal-label {
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .subtotal-value {
            font-size: 12px;
        }
    }
</style>
@endpush

{{-- Diletakkan setelah stylesheet global agar orientasi laporan ini tidak kembali menjadi portrait. --}}
<style media="print">
    @page { size: A4 landscape; margin: 7mm; }
</style>

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <!-- Kop laporan untuk cetak -->
        <div class="kop-laporan d-none d-print-block">
            <div style="display: flex; align-items: center; margin-bottom: 20px;">
                <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo" style="width: 90px; height: 90px; margin-right: 20px; border-radius: 50%;">
                <div style="font-weight: bold; font-size: 16px; line-height: 1.3;">
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    SD IT Permata Insani Islamic School<br>
                    <span style="font-weight: normal; font-size: 14px;">Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                </div>
            </div>
        </div>
        <hr class="print-hr d-none d-print-block">
        <div class="tanggal-laporan d-none d-print-block">
            Dicetak pada: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN TAGIHAN SISWA</h3>

        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-money-bill-wave"></i>
                Laporan Tagihan Siswa
            </h1>
            <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <button class="btn-primary print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.tagihan_siswa.excel', request()->query()) }}" class="btn-primary export-btn">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>

        <div class="filter-section">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i> Filter Data
            </h3>
            
            <form method="GET" action="{{ route('laporan.tagihan_siswa') }}" id="filterForm" class="report-filter-form pi-filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="sekolah_id">
                            <i class="fas fa-school"></i> Sekolah
                        </label>
                        <select name="sekolah_id" id="sekolah_id" class="form-control">
                            <option value="">Semua Sekolah</option>
                            @foreach($daftarSekolah as $s)
                                <option value="{{ $s->id }}" {{ request('sekolah_id') == $s->id ? 'selected' : '' }}>{{ $s->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kelas_id">
                            <i class="fas fa-chalkboard"></i> Kelas
                        </label>
                        <select name="kelas_id"
                                id="kelas_id"
                                class="form-control"
                                data-class-filter-for="sekolah_id"
                                data-all-label="Semua Kelas">
                            <option value="">Semua Kelas</option>
                            @foreach($daftarKelas as $k)
                                @php
                                    $className = trim((string) $k->nama_kelas);
                                    $classLabel = in_array($className, ['', '-', '–'], true)
                                        ? $k->label_tingkat
                                        : $k->label_tingkat.' · '.$className;
                                @endphp
                                <option value="{{ $k->id }}"
                                        data-school-id="{{ $k->sekolah_id }}"
                                        data-school-name="{{ $k->sekolah?->nama_sekolah }}"
                                        data-class-label="{{ $classLabel }}"
                                        {{ request('kelas_id') == $k->id ? 'selected' : '' }}>
                                    {{ $classLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_mulai">
                            <i class="fas fa-calendar-alt"></i> Tanggal Mulai
                        </label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" 
                               value="{{ request('tanggal_mulai') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_selesai">
                            <i class="fas fa-calendar-check"></i> Tanggal Selesai
                        </label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai" 
                               value="{{ request('tanggal_selesai') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="search">
                            <i class="fas fa-search"></i> Cari
                        </label>
                        <input type="text" name="search" id="search" 
                               value="{{ request('search') }}" 
                               placeholder="Siswa, NIS, tagihan, transaksi, atau kuitansi..."
                               class="form-control">
                    </div>

                </div>

                <div class="filter-actions report-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Data
                    </button>
                    <a href="{{ route('laporan.tagihan_siswa') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>

                <div id="warning-message" class="warning-message">
                    <p><i class="fas fa-exclamation-triangle"></i> <span id="warning-text"></span></p>
                </div>
            </form>
        </div>

        <div class="subtotal-section">
            <h3 class="subtotal-title">Ringkasan Laporan</h3>
            <div class="subtotal-grid">
                <div class="subtotal-item">
                    <div class="subtotal-label">Transaksi</div>
                    <div class="subtotal-value">{{ number_format($reportSummary['transactions'], 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Siswa Membayar</div>
                    <div class="subtotal-value">{{ number_format($reportSummary['students'], 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Nilai Tagihan</div>
                    <div class="subtotal-value">Rp {{ number_format($reportSummary['gross'], 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Potongan</div>
                    <div class="subtotal-value">Rp {{ number_format($reportSummary['discount'], 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Total Diterima</div>
                    <div class="subtotal-value">Rp {{ number_format($reportSummary['received'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <!-- Subtotal Section -->
        @if(isset($subtotal) && $subtotal->count() > 0)
        <div class="subtotal-section">
            <h3 class="subtotal-title">Subtotal Berdasarkan Metode Pembayaran</h3>
            <div class="subtotal-grid">
                <div class="subtotal-item">
                    <div class="subtotal-label">Tunai</div>
                    <div class="subtotal-value">Rp {{ number_format($subtotal['tunai'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Transfer</div>
                    <div class="subtotal-value">Rp {{ number_format($subtotal['transfer'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">KJC</div>
                    <div class="subtotal-value">Rp {{ number_format($subtotal['kjc'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="subtotal-item">
                    <div class="subtotal-label">Tabungan</div>
                    <div class="subtotal-value">Rp {{ number_format($subtotal['tabungan'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-table"></i> Rincian Pembayaran Tagihan
                </h3>
            </div>
            
            <div class="table-responsive">
                @if($pembayarans->count() > 0)
                    <table class="modern-table payment-report-table">
                        <colgroup>
                            <col style="width:3%">
                            <col style="width:9%">
                            <col style="width:13%">
                            <col style="width:13%">
                            <col style="width:14%">
                            <col style="width:17%">
                            <col style="width:8%">
                            <col style="width:8%">
                            <col style="width:8%">
                            <col style="width:7%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Bayar</th>
                                <th>No. Kuitansi</th>
                                <th>Siswa</th>
                                <th>Sekolah / Kelas</th>
                                <th>Tagihan / Periode</th>
                                <th>Nilai</th>
                                <th>Potongan</th>
                                <th>Diterima</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pembayarans as $i => $p)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ optional($p->tanggal_bayar)->format('d/m/Y') }}</td>
                                    <td>
                                        <strong>{{ $p->nomor_kwitansi ?: '-' }}</strong><br>
                                        <small>{{ $p->transaction_id ?: 'Transaksi #' . $p->id }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $p->siswa?->nama ?? '-' }}</strong><br>
                                        <small>NIS {{ $p->siswa?->nis ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $p->siswa?->sekolah?->nama_sekolah ?? '-' }}</strong><br>
                                        <small>{{ $p->siswa?->kelas?->tingkat ?? '-' }} {{ $p->siswa?->kelas?->nama_kelas ?? '' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $p->tagihan->nama_tagihan ?? 'Tagihan' }}</strong><br>
                                        <small>{{ $p->periode ?: ($p->tagihan->periode ?? '-') }}</small>
                                    </td>
                                    <td>Rp {{ number_format((float) $p->jumlah_bayar + (float) $p->diskon, 0, ',', '.') }}</td>
                                    <td>{{ (float) $p->diskon > 0 ? 'Rp ' . number_format($p->diskon, 0, ',', '.') : '-' }}</td>
                                    <td><strong>Rp {{ number_format($p->jumlah_bayar, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @if($p->metode_bayar == 'tunai')
                                            <span class="badge badge-success">
                                                <i class="fas fa-money-bill-wave"></i> Tunai
                                            </span>
                                        @else
                                            <span class="badge badge-info">
                                                <i class="fas fa-credit-card"></i> {{ ucfirst($p->metode_bayar) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" style="text-align: right;">Total:</th>
                                <th>Rp {{ number_format($reportSummary['gross'], 0, ',', '.') }}</th>
                                <th>Rp {{ number_format($reportSummary['discount'], 0, ',', '.') }}</th>
                                <th>Rp {{ number_format($reportSummary['received'], 0, ',', '.') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada data pembayaran yang ditemukan</p>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Footer untuk cetak -->
        <div class="print-footer d-none d-print-block">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <div class="signature-line"></div>
                    <p class="signature-name">________________________</p>
                    <p class="signature-title">NIP. ________________________</p>
                </div>
                <div class="signature-box">
                    <p>Jambi, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                    <p>Bendahara</p>
                    <div class="signature-line"></div>
                    <p class="signature-name">________________________</p>
                    <p class="signature-title">NIP. ________________________</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Date validation
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    const warningMessage = document.getElementById('warning-message');
    const warningText = document.getElementById('warning-text');

    function validateDates() {
        const mulai = new Date(tanggalMulai.value);
        const selesai = new Date(tanggalSelesai.value);
        
        warningMessage.style.display = 'none';
        
        if (tanggalMulai.value && tanggalSelesai.value) {
            if (mulai > selesai) {
                warningText.textContent = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai!';
                warningMessage.style.display = 'block';
                return false;
            }
        }
        
        return true;
    }

    tanggalMulai.addEventListener('change', validateDates);
    tanggalSelesai.addEventListener('change', validateDates);

    // Form submission validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
        }
    });
});
</script>
@endsection
