@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Laporan Semua Transaksi')

@section('content')
@push('page-styles')
<style>
    .all-transaction-table { table-layout: fixed; min-width: 1320px; }
    .all-transaction-table th, .all-transaction-table td { font-size: .76rem !important; line-height: 1.35; overflow-wrap: anywhere; padding: .65rem .55rem !important; }
    .report-type-badge { border-radius: 999px; display: inline-flex; font-size: .68rem; font-weight: 800; padding: .3rem .55rem; white-space: nowrap; }
    .report-type-badge.is-tagihan { background: #dbeafe; color: #1d4ed8; }
    .report-type-badge.is-koperasi { background: #dcfce7; color: #166534; }
    .report-type-badge.is-pemasukan { background: #fef3c7; color: #92400e; }
    .report-note { color: #64748b; display: block; font-size: .7rem; margin-top: .25rem; }
    @media print {
        .all-transaction-table { min-width: 0 !important; width: 100% !important; }
        .all-transaction-table th, .all-transaction-table td { font-size: 7px !important; line-height: 1.2 !important; padding: 2px 3px !important; }
        .report-type-badge { background: none !important; color: #000 !important; font-size: 7px; padding: 0; }
    }
</style>
@endpush
<style media="print">@page { size: A4 landscape; margin: 7mm; }</style>

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <div class="kop-laporan d-none d-print-block">
            <div>
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo">
                <div>
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    <span>Laporan administrasi sekolah</span>
                </div>
            </div>
        </div>
        <hr class="print-hr d-none d-print-block">
        <div class="tanggal-laporan d-none d-print-block">Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</div>
        <h3 class="print-title d-none d-print-block">LAPORAN SEMUA TRANSAKSI</h3>

        <div class="page-header no-print">
            <h1 class="page-title"><i class="fas fa-chart-line"></i> Laporan Semua Transaksi</h1>
            <div class="header-actions">
                <button type="button" class="btn-primary print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                @if(Auth::guard('web')->user()?->hasPermission('laporan.export'))
                    <a class="btn-primary export-btn" href="{{ route('laporan.transaksi.excel', request()->query()) }}"><i class="fas fa-file-excel"></i> Download Excel</a>
                @endif
            </div>
        </div>

        <div class="filter-section no-print">
            <h3 class="filter-title"><i class="fas fa-filter"></i> Filter Data</h3>
            <form method="GET" action="{{ route('laporan.transaksi') }}" class="report-filter-form pi-filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="search"><i class="fas fa-search"></i> Cari transaksi</label>
                        <input class="form-control" id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Referensi, siswa, sumber, atau rincian...">
                    </div>
                    <div class="form-group">
                        <label for="sekolah_id"><i class="fas fa-school"></i> Sekolah</label>
                        <select class="form-control" id="sekolah_id" name="sekolah_id">
                            <option value="">Semua sekolah</option>
                            @foreach($daftarSekolah as $school)
                                <option value="{{ $school->id }}" @selected((string) request('sekolah_id') === (string) $school->id)>{{ $school->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jenis_transaksi"><i class="fas fa-layer-group"></i> Jenis transaksi</label>
                        <select class="form-control" id="jenis_transaksi" name="jenis_transaksi">
                            <option value="">Semua transaksi</option>
                            <option value="tagihan" @selected(request('jenis_transaksi') === 'tagihan')>Tagihan siswa</option>
                            <option value="koperasi" @selected(request('jenis_transaksi') === 'koperasi')>Koperasi</option>
                            <option value="pemasukan" @selected(request('jenis_transaksi') === 'pemasukan')>Pemasukan lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_mulai"><i class="fas fa-calendar"></i> Mulai</label>
                        <input class="form-control" id="tanggal_mulai" name="tanggal_mulai" type="date" value="{{ request('tanggal_mulai') }}">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_selesai"><i class="fas fa-calendar-check"></i> Selesai</label>
                        <input class="form-control" id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ request('tanggal_selesai') }}">
                    </div>
                </div>
                <div class="filter-actions report-filter-actions">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tampilkan</button>
                    <a class="btn btn-secondary" href="{{ route('laporan.transaksi') }}" data-filter-reset><i class="fas fa-rotate-left"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="summary-section">
            <h3 class="subtotal-title">Ringkasan Laporan</h3>
            <div class="summary-grid">
                <div class="summary-item"><div class="summary-label">Semua Transaksi</div><div class="summary-value">{{ number_format($summary['transactions'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Tagihan Siswa</div><div class="summary-value">{{ number_format($summary['student_bills'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Koperasi</div><div class="summary-value">{{ number_format($summary['cooperative'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Pemasukan Lainnya</div><div class="summary-value">{{ number_format($summary['other_income'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Nilai Transaksi</div><div class="summary-value">Rp {{ number_format($summary['gross'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Potongan</div><div class="summary-value">Rp {{ number_format($summary['discount'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Total Diterima</div><div class="summary-value">Rp {{ number_format($summary['received'], 0, ',', '.') }}</div></div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header"><h3 class="table-title"><i class="fas fa-table"></i> Rincian Semua Transaksi</h3></div>
            <div class="table-responsive">
                @if($transactions->count())
                    <table class="modern-table all-transaction-table">
                        <colgroup>
                            <col style="width:3%"><col style="width:7%"><col style="width:8%"><col style="width:9%">
                            <col style="width:12%"><col style="width:11%"><col style="width:7%"><col style="width:19%">
                            <col style="width:4%"><col style="width:7%"><col style="width:6%"><col style="width:7%">
                        </colgroup>
                        <thead><tr>
                            <th>No</th><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th>Siswa / Sumber</th><th>Sekolah</th>
                            <th>Kelas</th><th>Rincian</th><th>Item</th><th>Nilai</th><th>Potongan</th><th>Diterima</th>
                        </tr></thead>
                        <tbody>
                        @foreach($transactions as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['date']->format('d/m/Y') }}</td>
                                <td><span class="report-type-badge is-{{ $row['type'] }}">{{ $row['type_label'] }}</span></td>
                                <td><strong>{{ $row['reference'] }}</strong><span class="report-note">{{ $row['method'] }}</span></td>
                                <td>{{ $row['party'] }}</td>
                                <td>{{ $row['school'] }}</td>
                                <td>{{ $row['class'] }}</td>
                                <td>{{ $row['description'] }}@if($row['note'])<span class="report-note">Catatan: {{ $row['note'] }}</span>@endif</td>
                                <td>{{ number_format($row['item_count'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($row['gross'], 0, ',', '.') }}</td>
                                <td>{{ $row['discount'] > 0 ? 'Rp ' . number_format($row['discount'], 0, ',', '.') : '-' }}</td>
                                <td><strong>Rp {{ number_format($row['received'], 0, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr>
                            <th colspan="9" style="text-align:right">Total</th>
                            <th>Rp {{ number_format($summary['gross'], 0, ',', '.') }}</th>
                            <th>Rp {{ number_format($summary['discount'], 0, ',', '.') }}</th>
                            <th>Rp {{ number_format($summary['received'], 0, ',', '.') }}</th>
                        </tr></tfoot>
                    </table>
                @else
                    <div class="empty-state"><i class="fas fa-inbox"></i><p>Tidak ada transaksi yang sesuai dengan filter.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
