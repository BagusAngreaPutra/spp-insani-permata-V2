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
    
    /* Filter Form Styles */
    .filter-form {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        display: flex;
        gap: 1rem;
        align-items: end;
        flex-wrap: wrap;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }
    
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        background: white;
        font-size: 1rem;
    }
    
    .filter-btn, .reset-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: none;
    }
    
    .reset-btn {
        background: #f3f4f6;
        color: #374151;
        text-decoration: none;
    }
    
    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }
    
    .reset-btn:hover {
        background: #e5e7eb;
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
        .filter-form,
        nav,
        header {
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
    }
</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <div class="kop-laporan d-none d-print-block">
            <div style="display: flex; align-items: center; margin-bottom: 20px; text-align: center; justify-content: center;">
                <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo" style="width: 90px; height: 90px; margin-right: 20px; border-radius: 50%;">
                <div style="font-weight: bold; font-size: 16px; line-height: 1.3; text-align: left;">
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    SD IT Permata Insani Islamic School<br>
                    <span style="font-weight: normal; font-size: 14px;">Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                </div>
            </div>
        </div>
        <hr class="print-hr d-none d-print-block">
        <div class="tanggal-laporan d-none d-print-block">
            Tanggal Laporan: {{ $tanggalLaporan }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA PEMASUKAN</h3>

        <div class="page-header no-print">
            <h2 class="page-title">
                <i class="fas fa-money-bill-wave"></i> Laporan pemasukan
            </h2>
            <div>
                <button onclick="window.print()" class="btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.pemasukan.excel') }}" class="btn-primary">Download Excel</a>
            </div>
        </div>

        {{-- 🔎 Form Filter Pemasukan --}}
        <form method="GET" action="{{ route('laporan.pemasukan') }}" class="filter-form no-print">
            <div class="form-group">
                <label for="sekolah_id">Pilih Sekolah</label>
                <select name="sekolah_id" id="sekolah_id">
                    <option value="">Semua Sekolah</option>
                    @foreach($daftarSekolah as $p)
                        <option value="{{ $p->id }}" {{ request('sekolah_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_sekolah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Tampilkan Data
            </button>

            <a href="{{ route('laporan.pemasukan') }}" class="reset-btn">
                <i class="fas fa-redo"></i> Reset
            </a>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th>Nama Sekolah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pemasukans as $i => $p)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $p->tanggal }}</td>
                            <td>Rp {{ number_format($p->jumlah, 2, ',', '.') }}</td>
                            <td>{{ $p->sumber ?? '-' }}</td>
                            <td>{{ $p->keterangan ?? '-' }}</td>
                            <td>{{ $p->sekolah->nama_sekolah ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
@endsection
