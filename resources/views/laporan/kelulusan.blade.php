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

    /* ====== STYLE KHUSUS CETAK ====== */
    @media print {
@page{
            size: landscape;
        }
        /* Reset dan pengaturan dasar */
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: A4;
            margin: 1.5cm 1cm;
        }

        /* Sembunyikan elemen-elemen non-cetak */
        aside.sidebar,
        .page-header,
        .btn-primary,
        .btn-print,
        .no-print,
        nav,
        header,
        .sidebar {
            display: none !important;
        }

        /* Atur ulang layout utama */
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #fff !important;
            font-family: 'Times New Roman', serif !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
            color: #000 !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #fff !important;
            position: static !important;
        }

        .content-area {
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Kop surat */
        .kop-laporan {
            text-align: center;
            margin-bottom: 15px;
            page-break-inside: avoid;
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
            font-size: 11px;
            color: #333;
        }

        /* Garis pemisah */
        hr.print-hr {
            border: none;
            border-top: 2px solid #000;
            margin: 15px 0;
            height: 0;
        }

        /* Tanggal laporan */
        .tanggal-laporan {
            text-align: right;
            font-size: 11px;
            margin: 10px 0;
            font-style: italic;
        }

        /* Judul laporan */
        h3.print-title {
            text-align: center;
            margin: 15px 0 20px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            page-break-inside: avoid;
        }

        /* Kontainer tabel */
        .table-container {
            background: none !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            backdrop-filter: none !important;
            margin: 0 !important;
            padding: 0 !important;
            page-break-inside: avoid;
        }

        /* Tabel utama */
        .modern-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important;
        }

        .modern-table th,
        .modern-table td {
            border: 1px solid #000 !important;
            padding: 4px 6px !important;
            text-align: left !important;
            vertical-align: top !important;
            background: none !important;
            color: #000 !important;
        }

        .modern-table th {
            background-color: #f0f0f0 !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
        }

        /* Lebar kolom tetap */
        .modern-table th:nth-child(1),
        .modern-table td:nth-child(1) {
            width: 5% !important;
        }

        .modern-table th:nth-child(2),
        .modern-table td:nth-child(2) {
            width: 20% !important;
        }

        .modern-table th:nth-child(3),
        .modern-table td:nth-child(3) {
            width: 18% !important;
        }

        .modern-table th:nth-child(4),
        .modern-table td:nth-child(4) {
            width: 12% !important;
        }

        .modern-table th:nth-child(5),
        .modern-table td:nth-child(5) {
            width: 15% !important;
        }

        .modern-table th:nth-child(6),
        .modern-table td:nth-child(6) {
            width: 12% !important;
        }

        .modern-table th:nth-child(7),
        .modern-table td:nth-child(7) {
            width: 18% !important;
        }

        /* Pastikan teks tidak terpotong */
        .modern-table td {
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            hyphens: auto !important;
        }

        /* Footer atau informasi tambahan jika ada */
        .print-footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            page-break-inside: avoid;
        }

        /* Pengaturan untuk tabel yang panjang */
        .modern-table {
            page-break-after: auto;
        }

        .modern-table thead {
            page-break-after: avoid;
        }

        .modern-table tbody tr {
            page-break-inside: avoid;
        }

        /* Jika tabel terlalu panjang, bagi per halaman */
        .page-break {
            page-break-before: always;
        }

        /* Styling untuk data kosong */
        .modern-table td:empty::after {
            content: "-";
            color: #666;
        }

        /* Tampilkan elemen yang hanya untuk print */
        .d-print-block {
            display: block !important;
        }

        .d-print-inline {
            display: inline !important;
        }

        .d-print-inline-block {
            display: inline-block !important;
        }

        /* Sembunyikan elemen yang tidak perlu saat print */
        .d-print-none {
            display: none !important;
        }
        
        /* Support for page orientation */
        .landscape {
            size: A4 landscape;
        }
    }

</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        {{-- KOP LAPORAN UNTUK CETAK --}}
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
        <h3 class="print-title d-none d-print-block">LAPORAN DATA KELULUSAN</h3>

        <div class="page-header no-print">
            <h2 class="page-title">
                <i class="fas fa-graduation-cap"></i> Laporan Kelulusan
            </h2>
            <div>
                <button onclick="window.print()" class="btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.kelulusan.excel') }}" class="btn-primary">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>
        
        

        {{-- Form Filter dan Search --}}
        <form method="GET" action="{{ route('laporan.kelulusan') }}" class="filter-form pi-filter-form no-print">
            <div class="filter-grid pi-filter-grid">
                {{-- Filter Sekolah --}}
                <div class="form-group pi-filter-field">
                    <label for="sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($semuaSekolah as $s)
                            <option value="{{ $s->id }}" {{ request('sekolah_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Kelas --}}
                <div class="form-group pi-filter-field">
                    <label for="kelas_id">Kelas</label>
                    <select name="kelas_id"
                            id="kelas_id"
                            class="form-control"
                            data-class-filter-for="sekolah_id"
                            data-all-label="Semua Kelas">
                        <option value="">Semua Kelas</option>
                        @foreach($semuaKelas as $k)
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

                {{-- Filter Tanggal Lulus --}}
                <div class="form-group pi-filter-field">
                    <label for="tanggal_lulus">Tanggal Lulus</label>
                    <input type="date" name="tanggal_lulus" id="tanggal_lulus" value="{{ request('tanggal_lulus') }}" class="form-control">
                </div>

                {{-- Search Nama Siswa --}}
                <div class="form-group pi-filter-field">
                    <label for="search">Cari Nama</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        placeholder="Cari nama siswa..." class="form-control">
                </div>

                {{-- Tombol Submit --}}
                <div class="form-group pi-filter-action-group">
                    <label style="visibility:hidden;">Tampilkan</label>
                    <button type="submit" class="btn btn-primary pi-filter-control">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>
        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Sekolah</th>
                        <th>Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Lulus</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($kelulusan as $index => $k)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $k->siswa->nama ?? '-' }}</td>
                        <td class="align-middle text-nowrap">{{ $k->sekolah->nama_sekolah ?? '-' }}</td>
                        <td class="align-middle text-nowrap">
                            @if($k->kelas)
                                {{ $k->kelas->kelas }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $k->tahunAjaran->nama_tahun ?? '-' }}</td>
                        <td>
                            @if($k->tanggal_lulus)
                                {{ \Carbon\Carbon::parse($k->tanggal_lulus)->translatedFormat('d-m-Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $k->keterangan ?? '-' }}</td>
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
