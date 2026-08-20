@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
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

    /* 🔎 Filter Form */
    .filter-form {
        display: flex;
        gap: 1.5rem;
        align-items: flex-end;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        background: rgba(255, 255, 255, 0.9);
        padding: 2rem;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.1);
        animation: fadeInUp 0.7s ease-out;
    }
    .filter-form .form-group {
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
    }
    .filter-form .form-group:hover {
        transform: translateY(-2px);
    }
    .filter-form label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #166534;
        font-size: 0.9rem;
    }
    .filter-form select,
    .filter-form input {
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border: 2px solid rgba(34, 197, 94, 0.2);
        transition: all 0.3s ease;
        background: white;
        font-size: 0.9rem;
        min-width: 220px;
    }
    .filter-form select:focus,
    .filter-form input:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        transform: translateY(-1px);
    }

    /* Tombol Filter */
    .filter-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a, #15803d);
        color: #fff;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.4s ease;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
        height: 48px;
        padding: 0 1.5rem;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .filter-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    .filter-btn:hover::before { left: 100%; }
    .filter-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(34, 197, 94, 0.4);
    }

    /* Tombol Reset */
    .reset-btn {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        height: 48px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .reset-btn:hover {
        background: linear-gradient(135deg, #4b5563, #374151);
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(107, 114, 128, 0.3);
    }

    .table-container {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.2);
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
        border-bottom: 1px solid rgba(220,252,231,0.8);
        text-align: left;
        vertical-align: top;
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
        background: rgba(34,197,94,0.05);
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(20px);
        padding: 2rem;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    .page-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg,#2d3748,#4a5568);
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
        box-shadow: 0 4px 12px rgba(37,99,235,0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37,99,235,0.4);
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

    .school-name-header {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-weight: 600;
        font-size: 1.2rem;
    }

    @keyframes fadeInUp {
        0% { opacity:0; transform:translateY(20px); }
        100% { opacity:1; transform:translateY(0); }
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

    /* Informasi cetak */
    .print-info {
        text-align: right;
        font-size: 11px;
        margin-bottom: 15px;
        font-style: italic;
    }

    /* Footer cetak */
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
        height: 1px;
        background: #000;
        margin: 40px 0 10px 0;
    }

    .signature-name,
    .signature-title {
        margin: 5px 0;
        font-size: 11px;
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
        font-family: 'Times New Roman', serif !important;
        margin: 0 !important;
        page-break-inside: auto;
    }

    /* Header tabel */
    .modern-table thead {
        display: table-header-group;
    }

    .modern-table thead tr {
        page-break-inside: avoid;
        page-break-after: avoid;
    }

    .modern-table th {
        border: 1px solid #000 !important;
        padding: 6px 4px !important;
        text-align: center !important;
        background: #f5f5f5 !important;
        color: #000 !important;
        font-weight: bold !important;
        font-size: 9px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        vertical-align: middle !important;
        line-height: 1.2 !important;
    }

    /* Body tabel */
    .modern-table tbody {
        display: table-row-group;
    }

    .modern-table tbody tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    .modern-table td {
        border: 1px solid #000 !important;
        padding: 5px 4px !important;
        text-align: left !important;
        vertical-align: top !important;
        font-size: 9px !important;
        line-height: 1.3 !important;
        color: #000 !important;
        background: #fff !important;
    }

    /* Hover effects dihilangkan untuk print */
    .modern-table tbody tr:hover {
        background: #fff !important;
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
    
    .school-name-header {
        background: #22c55e !important;
        color: #000 !important;
        padding: 0.5rem !important;
        margin-bottom: 0.5rem !important;
        font-weight: bold !important;
        font-size: 12px !important;
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
            Dicetak pada: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA KELAS</h3>

        <!-- Page Header -->
        <div class="page-header no-print">
            <h2 class="page-title">
                <i class="fas fa-chalkboard"></i>
                Laporan kelas
            </h2>
            <div>
                <a href="javascript:window.print()" class="btn-primary">
                    <i class="fas fa-print"></i> Print
                </a>
                <a href="{{ route('laporan.kelas.excel') }}" class="btn-primary">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="filter-form no-print">
            <div class="form-group">
                <label for="sekolah_id">Sekolah:</label>
                <select name="sekolah_id" id="sekolah_id">
                    <option value="">Semua Sekolah</option>
                    @foreach($daftarSekolah as $sekolahItem)
                        <option value="{{ $sekolahItem->id }}" {{ request('sekolah_id') == $sekolahItem->id ? 'selected' : '' }}>
                            {{ $sekolahItem->nama_sekolah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="tingkat">Tingkat:</label>
                <select name="tingkat" id="tingkat">
                    <option value="">Semua Tingkat</option>
                    <option value="1" {{ request('tingkat') == '1' ? 'selected' : '' }}>1</option>
                    <option value="2" {{ request('tingkat') == '2' ? 'selected' : '' }}>2</option>
                    <option value="3" {{ request('tingkat') == '3' ? 'selected' : '' }}>3</option>
                    <option value="4" {{ request('tingkat') == '4' ? 'selected' : '' }}>4</option>
                </select>
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <a href="{{ route('laporan.kelas') }}" class="reset-btn">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-responsive">
                @if($kelas->count() > 0)
                    @php
                        // Terapkan filter tingkat jika ada
                        if(request('tingkat')) {
                            $kelas = $kelas->filter(function($kelas) {
                                return $kelas->tingkat == request('tingkat');
                            });
                        }
                        
                        $groupedkelas = $kelas->groupBy('sekolah_id');
                    @endphp

                    @foreach($groupedkelas as $sekolahId => $kelasBySekolah)
                        @php
                            $firstKelas = $kelasBySekolah->first();
                        @endphp
                        
                        <div class="school-name-header">
                            {{ $firstKelas->sekolah->nama_sekolah ?? 'Sekolah Tidak Diketahui' }}
                        </div>
                        
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kelas</th>
                                    <th>Jumlah Siswa</th>
                                   
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kelasBySekolah as $index => $kelas)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $kelas->kelas }}</td>
                                        <td>{{ $kelas->siswa->count() }}</td>
                                        
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        @if(!$loop->last)
                            <div style="height: 2rem;"></div>
                        @endif
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada data kelas yang ditemukan</p>
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
