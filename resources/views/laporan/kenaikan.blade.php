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
    
    .filter-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
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
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        background: white;
        font-size: 1rem;
    }
    
    .btn {
        padding: 0.75rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
        text-decoration: none;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .header-actions {
        display: flex;
        gap: 1rem;
    }
    
    .print-btn, .export-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ====== STYLE KHUSUS CETAK ====== */
    @media print {
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
    }
</style>
@endpush

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
            Tanggal Laporan: {{ $tanggalLaporan }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA KENAIKAN KELAS</h3>

        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-arrow-up"></i>
                Laporan Kenaikan Kelas
            </h1>
            <div class="header-actions">
                <button class="btn btn-primary print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.kenaikan.excel') }}" class="btn btn-primary export-btn">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>

        <div class="filter-section">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i> Filter Data
            </h3>
            
            <form method="GET" action="{{ route('laporan.kenaikan') }}" id="filterForm" class="report-filter-form pi-filter-form">
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
                        <label for="tahun_ajaran_id">
                            <i class="fas fa-calendar-alt"></i> Tahun Ajaran
                        </label>
                        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-control">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach($daftarTahunAjaran as $t)
                                <option value="{{ $t->id }}" {{ request('tahun_ajaran_id') == $t->id ? 'selected' : '' }}>{{ $t->nama_tahun }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="filter-actions report-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Data
                    </button>
                    <a href="{{ route('laporan.kenaikan') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel Data --}}
        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Sekolah</th>
                        <th>Kelas Awal</th>
                        <th>Kelas Baru</th>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Kenaikan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayat as $index => $r)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $r->siswa->nama }}</td>
                            <td>{{ $r->siswa->sekolah->nama_sekolah ?? '-' }}</td>
                            <td>{{ $r->kelasAwal?->kelas ?? '-' }}</td>
                            <td>{{ $r->kelasBaru?->kelas ?? '-' }}</td>
                            <td>{{ $r->tahunAjaran->nama_tahun ?? '-' }}</td>
                            <td>
                                @if($r->tanggal_kenaikan)
                                    {{ \Carbon\Carbon::parse($r->tanggal_kenaikan)->translatedFormat('d-m-Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $r->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Tidak ada data.</td>
                        </tr>
                    @endforelse
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
