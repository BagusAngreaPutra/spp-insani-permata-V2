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
        .main-content { margin-left: 0; width: 100%; position: relative; }
    }
    .content-area { padding: 3rem 2.5rem; }
    .page-header, .filter-section, .summary-section, .table-container {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(34, 197, 94, 0.12);
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.1);
        margin-bottom: 2rem;
    }
    .page-header {
        align-items: center;
        display: flex;
        justify-content: space-between;
        padding: 2rem;
    }
    .page-title {
        align-items: center;
        color: #14532d;
        display: flex;
        font-size: 2rem;
        font-weight: 800;
        gap: 0.75rem;
        margin: 0;
    }
    .btn-primary, .btn-secondary {
        border: none;
        border-radius: 12px;
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        font-weight: 700;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        text-decoration: none;
    }
    .btn-primary { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .btn-secondary { background: linear-gradient(135deg, #64748b, #475569); }
    .filter-section { padding: 2rem; }
    .filter-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .form-group label {
        color: #166534;
        display: block;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }
    .form-control {
        border: 2px solid rgba(34, 197, 94, 0.18);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        width: 100%;
    }
    .filter-actions { display: flex; gap: 0.75rem; margin-top: 1rem; }
    .summary-section { padding: 2rem; }
    .summary-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .summary-item {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-radius: 14px;
        padding: 1.25rem;
        text-align: center;
    }
    .summary-label { color: #166534; font-weight: 700; margin-bottom: 0.4rem; }
    .summary-value { color: #14532d; font-size: 1.4rem; font-weight: 800; }
    .table-container { overflow: hidden; }
    .table-header { padding: 1.5rem 2rem 0; }
    .table-title { color: #14532d; font-size: 1.5rem; font-weight: 800; margin: 0; }
    .modern-table {
        border-collapse: collapse;
        font-size: 0.92rem;
        width: 100%;
    }
    .modern-table th, .modern-table td {
        border-bottom: 1px solid rgba(220, 252, 231, 0.8);
        padding: 1rem 1.1rem;
        text-align: left;
        vertical-align: top;
    }
    .modern-table th {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        color: #166534;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .empty-state { color: #64748b; padding: 3rem; text-align: center; }
    .kop-laporan, .print-footer { display: none; }

    @media print {
        @page { size: landscape; margin: 5mm; }
        .app-sidebar, .sidebar-mobile-menu-btn, .page-header, .filter-section, nav, header { display: none !important; }
        body, .main-content { background: #fff !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .main-content { position: static !important; }
        .content-area { padding: 10px !important; }
        .kop-laporan, .print-footer { display: block; }
        .summary-section { box-shadow: none; border: 1px solid #000; border-radius: 0; margin-bottom: 10px; padding: 10px; }
        .summary-grid { grid-template-columns: repeat(3, 1fr); }
        .summary-item { background: none; border: 1px solid #000; border-radius: 0; padding: 8px; }
        .summary-value, .summary-label { color: #000; font-size: 12px; }
        .table-container { box-shadow: none; border: none; border-radius: 0; }
        .table-header { padding: 0; text-align: center; }
        .table-title { color: #000; font-size: 18px; margin: 10px 0; }
        .modern-table { font-size: 10px; }
        .modern-table th, .modern-table td { border: 1px solid #000; padding: 4px; }
        .modern-table th { background: #f0f0f0; color: #000; letter-spacing: 0; text-transform: none; }
        .signature-section { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { text-align: center; width: 45%; }
        .signature-line { border-top: 1px solid #000; margin: 60px 0 10px; }
    }
</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <div class="kop-laporan d-none d-print-block">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo" style="width: 90px; height: 90px; margin-right: 20px; border-radius: 50%;">
                <div style="font-weight: bold; font-size: 16px; line-height: 1.3; text-align: left;">
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    SD IT Permata Insani Islamic School<br>
                    <span style="font-weight: normal; font-size: 14px;">Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                </div>
            </div>
        </div>
        <div class="tanggal-laporan d-none d-print-block">Tanggal Laporan: {{ $tanggalLaporan }}</div>
        <h3 class="print-title d-none d-print-block">LAPORAN KOPERASI</h3>

        <div class="page-header no-print">
            <h2 class="page-title"><i class="fas fa-store"></i> Laporan Koperasi</h2>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="document.fonts.ready.then(() => window.print())" class="btn-primary"><i class="fas fa-print"></i> Print</button>
                <a href="{{ route('laporan.koperasi.excel', request()->query()) }}" class="btn-primary">
                    <i class="fas fa-file-excel"></i> Download Excel
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('laporan.koperasi') }}" class="filter-section report-filter-form pi-filter-form no-print">
            <div class="filter-grid">
                <div class="form-group">
                    <label for="sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($daftarSekolah as $item)
                            <option value="{{ $item->id }}" {{ request('sekolah_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="kelas_id">Kelas</label>
                    <select name="kelas_id"
                            id="kelas_id"
                            class="form-control"
                            data-class-filter-for="sekolah_id"
                            data-all-label="Semua Kelas">
                        <option value="">Semua Kelas</option>
                        @foreach($daftarKelas as $item)
                            @php
                                $className = trim((string) $item->nama_kelas);
                                $classLabel = in_array($className, ['', '-', '–'], true)
                                    ? $item->label_tingkat
                                    : $item->label_tingkat.' · '.$className;
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-school-id="{{ $item->sekolah_id }}"
                                    data-school-name="{{ $item->sekolah?->nama_sekolah }}"
                                    data-class-label="{{ $classLabel }}"
                                    {{ request('kelas_id') == $item->id ? 'selected' : '' }}>
                                {{ $classLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="form-group">
                    <label for="search">Cari</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Siswa, NIS, barang, kode transaksi">
                </div>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
                <a href="{{ route('laporan.koperasi') }}" class="btn-secondary">Reset</a>
            </div>
        </form>

        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Transaksi</div>
                    <div class="summary-value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Barang Terjual</div>
                    <div class="summary-value">{{ number_format($totalBarangTerjual, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Penjualan</div>
                    <div class="summary-value">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Siswa Bertransaksi</div>
                    <div class="summary-value">{{ number_format($totalSiswa, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Jenis Barang Terjual</div>
                    <div class="summary-value">{{ number_format($jenisBarangTerjual, 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Rata-rata Transaksi</div>
                    <div class="summary-value">Rp {{ number_format($rataRataTransaksi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Laporan Penjualan Koperasi</h3>
            </div>
            <table class="modern-table cooperative-report-table">
                <colgroup>
                    <col class="report-col-number">
                    <col class="report-col-date">
                    <col class="report-col-transaction">
                    <col class="report-col-student">
                    <col class="report-col-school">
                    <col class="report-col-items">
                    <col class="report-col-quantity">
                    <col class="report-col-total">
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Transaksi</th>
                        <th>Siswa</th>
                        <th>Sekolah/Kelas</th>
                        <th>Barang</th>
                        <th>Jumlah Item</th>
                        <th>Total</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penjualan as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ optional($item->tanggal)->format('d/m/Y') }}</td>
                            <td>{{ $item->kode_transaksi }}</td>
                            <td>
                                <strong>{{ $item->siswa->nama ?? '-' }}</strong><br>
                                <small>{{ $item->siswa->nis ?? '-' }}</small>
                            </td>
                            <td>
                                {{ $item->sekolah->nama_sekolah ?? '-' }}<br>
                                <small>{{ $item->siswa->kelas->kelas ?? '-' }}</small>
                            </td>
                            <td>
                                @foreach($item->details as $detail)
                                    {{ $detail->nama_barang }} ({{ $detail->jumlah }} x Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }})<br>
                                @endforeach
                            </td>
                            <td>{{ $item->details->sum('jumlah') }}</td>
                            <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                            <td>{{ $item->catatan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr class="report-empty-row pi-sort-fixed" data-sort-fixed>
                            <td colspan="9" class="report-table-empty">
                                <div class="report-empty-content">
                                    <span class="report-empty-icon" aria-hidden="true">
                                        <i class="fas fa-cart-shopping"></i>
                                    </span>
                                    <strong>Belum ada penjualan koperasi</strong>
                                    <span>Belum ditemukan transaksi untuk filter yang dipilih.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="print-footer d-none d-print-block">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <div class="signature-line"></div>
                    <p>________________________</p>
                    <p>NIP. ________________________</p>
                </div>
                <div class="signature-box">
                    <p>Jambi, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                    <p>Bendahara</p>
                    <div class="signature-line"></div>
                    <p>________________________</p>
                    <p>NIP. ________________________</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
