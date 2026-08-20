@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
    /* ===== STYLE NORMAL ===== */
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
    .filter-form {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        align-items: end;
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
    .filter-btn, .reset-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }
    .reset-btn {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
        box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        text-decoration: none;
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
    
    /* ===== STYLE CETAK ===== */
    @media print {
@page{
            size: landscape;
        }
        aside.sidebar,
        .page-header,
        .btn-primary,
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
        }
        .kop-laporan {
            text-align: center;
            margin-bottom: 20px;
        }
        .kop-laporan h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        h3.print-title {
            text-align: center;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
        }
        /* Footer untuk cetak */
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
        @page {
            margin: 5mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .modern-table th,
        .modern-table td {
            padding: 3px 5px;
        }
    }
</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <!-- Kop laporan untuk cetak -->
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
            Dicetak pada: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA SISWA</h3>

        <!-- Page Header -->
        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                Laporan siswa
            </h1>
            <div class="header-actions">
                <a href="#" class="btn-primary print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </a>
                <a href="{{ route('laporan.siswa.excel') }}" class="btn-primary export-btn">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="sekolah_id">Sekolah:</label>
                <select name="sekolah_id" id="sekolah_id" class="form-control">
                    <option value="">Semua Sekolah</option>
                    @foreach($daftarSekolah as $sekolah)
                        <option value="{{ $sekolah->id }}" {{ request('sekolah_id') == $sekolah->id ? 'selected' : '' }}>
                            {{ $sekolah->nama_sekolah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="kelas_id">Kelas:</label>
                <select name="kelas_id"
                        id="kelas_id"
                        class="form-control"
                        data-class-filter-for="sekolah_id"
                        data-all-label="Semua Kelas">
                    <option value="">Semua Kelas</option>
                    @foreach($daftarKelas as $kelas)
                        @php
                            $className = trim((string) $kelas->nama_kelas);
                            $classLabel = in_array($className, ['', '-', '–'], true)
                                ? $kelas->label_tingkat
                                : $kelas->label_tingkat.' · '.$className;
                        @endphp
                        <option value="{{ $kelas->id }}"
                                data-school-id="{{ $kelas->sekolah_id }}"
                                data-school-name="{{ $kelas->sekolah?->nama_sekolah }}"
                                data-class-label="{{ $classLabel }}"
                                {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $classLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="lulus" {{ request('status') == 'lulus' ? 'selected' : '' }}>Lulus</option>
                    <option value="keluar" {{ request('status') == 'keluar' ? 'selected' : '' }}>Keluar</option>
                    <option value="tidak_aktif" {{ request('status') == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <a href="{{ route('laporan.siswa') }}" class="reset-btn">
                <i class="fas fa-sync-alt"></i> Reset
            </a>
        </form>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">
                    <i class="fas fa-list"></i>
                    Daftar Siswa
                </h3>
            </div>
            
            <div class="table-responsive">
                @if($siswas->count() > 0)
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Jenis Kelamin</th>
                                <th>Kelas</th>
                                <th>Tempat, Tanggal Lahir</th>
                                <th>Nama Orang Tua</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($siswas as $siswa)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $siswa->nis }}</td>
                                    <td>{{ $siswa->nama }}</td>
                                    <td>{{ $siswa->jenis_kelamin }}</td>
                                    <td>{{ $siswa->kelas->tingkat }} {{ $siswa->kelas->nama_kelas }}</td>
                                    <td>{{ $siswa->tempat_lahir }}, {{ \Carbon\Carbon::parse($siswa->tanggal_lahir)->format('d/m/Y') }}</td>
                                    <td>{{ $siswa->nama_ayah }} / {{ $siswa->nama_ibu }}</td>
                                    <td>
                                        @if($siswa->status == 'aktif')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Aktif
                                            </span>
                                        @elseif($siswa->status == 'lulus')
                                            <span class="badge badge-info">
                                                <i class="fas fa-graduation-cap"></i> Lulus
                                            </span>
                                        @elseif($siswa->status == 'keluar')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-door-open"></i> Keluar
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                <i class="fas fa-times-circle"></i> Tidak Aktif
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada data siswa yang ditemukan</p>
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
@endsection
