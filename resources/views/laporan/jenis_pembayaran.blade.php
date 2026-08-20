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
    
    /* Tooltip styling */
    .tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
        margin-left: 0.5rem;
    }
    
    .tooltip i {
        color: #16a34a;
        font-size: 0.85rem;
    }
    
    .tooltip-content {
        visibility: hidden;
        position: absolute;
        background-color: #1f2937;
        color: #fff;
        text-align: left;
        padding: 0.75rem;
        border-radius: 8px;
        z-index: 1000;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        font-size: 0.85rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .tooltip-content::after {
        content: "";
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #1f2937 transparent transparent transparent;
    }
    
    .tooltip:hover .tooltip-content {
        visibility: visible;
        animation: tooltipFade 0.3s ease-in;
    }
    
    @keyframes tooltipFade {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    /* Target column should have max-width to prevent table from expanding */
    .modern-table td:nth-child(7) {
        max-width: 200px;
        word-wrap: break-word;
        overflow-wrap: break-word;
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
        
        /* Hide tooltips when printing */
        .tooltip,
        .tooltip-content {
            display: none !important;
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
        <h3 class="print-title d-none d-print-block">LAPORAN DATA JENIS PEMBAYARAN</h3>

        <div class="page-header no-print">
            <h2 class="page-title">
                <i class="fas fa-money-bill-wave"></i> Laporan Jenis Pembayaran
            </h2>
            <div>
                <button onclick="window.print()" class="btn-primary btn-print">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.jenis_pembayaran.excel') }}" class="btn-primary">Download Excel</a>
            </div>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Sekolah</th>
                        <th>Nama Pembayaran</th>
                        <th>Tipe</th>
                        <th>Nominal</th>
                        <th>Jatuh Tempo</th>
                        <th>Target</th>
                        <th>Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jenis_pembayarans as $i => $jp)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $jp->sekolah->nama_sekolah ?? '-' }}</td>
                        <td>{{ $jp->nama_pembayaran }}</td>
                        <td>{{ $jp->tipe }}</td>
                        <td>Rp {{ number_format($jp->nominal, 2, ',', '.') }}</td>
                        <td>
                            @if($jp->tipe == 'bulanan')
                                @if($jp->jatuh_tempo)
                                    Tanggal {{ \Carbon\Carbon::parse($jp->jatuh_tempo)->format('d') }}
                                @else
                                    -
                                @endif
                            @else
                                {{ $jp->jatuh_tempo ? \Carbon\Carbon::parse($jp->jatuh_tempo)->format('d-m-Y') : '-' }}
                            @endif
                        </td>
                        <td>
                            @if($jp->target_type == 'all')
                                Semua Siswa
                            @elseif($jp->target_type == 'specific_students')
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Siswa Tertentu ({{ $jp->siswa->count() }})</span>
                                    @if($jp->siswa->count() > 0)
                                        <div class="tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <div class="tooltip-content" style="white-space: normal; width: 250px; text-align: left;">
                                                @foreach($jp->siswa as $siswa)
                                                    <div>{{ $siswa->nama }} ({{ $siswa->nis }})</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif($jp->target_type == 'specific_classes')
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span>Kelas Tertentu ({{ $jp->kelas->count() }})</span>
                                    @if($jp->kelas->count() > 0)
                                        <div class="tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <div class="tooltip-content" style="white-space: normal; width: 200px; text-align: left;">
                                                @foreach($jp->kelas as $kelas)
                                                    <div>{{ $kelas->nama_kelas }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ $jp->created_at ? $jp->created_at->format('d-m-Y H:i') : '-' }}</td>
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
