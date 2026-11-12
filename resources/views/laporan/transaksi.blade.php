@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Laporan Semua Transaksi')

@section('content')
@push('page-styles')
<style>
    .report-type-badge { border-radius: 999px; display: inline-flex; font-size: .68rem; font-weight: 800; padding: .3rem .55rem; white-space: nowrap; }
    .report-type-badge.is-tagihan { background: #dbeafe; color: #1d4ed8; }
    .report-type-badge.is-koperasi { background: #dcfce7; color: #166534; }
    .report-type-badge.is-pemasukan { background: #fef3c7; color: #92400e; }
    .report-note { color: #64748b; display: block; font-size: .7rem; margin-top: .25rem; }
</style>
@endpush
<style media="print">@page { size: A4 landscape; margin: 7mm; }</style>

<div id="pi-report-page" class="main-content pi-report-page pi-transaction-report">
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
                <a class="btn-primary print-btn" href="{{ request()->fullUrlWithQuery(['print' => 1, 'page' => null]) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Cetak semua hasil</a>
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

        <section class="report-summary-panel" aria-label="Ringkasan transaksi dan metode pembayaran">
        <div class="summary-section">
            <h3 class="subtotal-title"><i class="fas fa-chart-simple" aria-hidden="true"></i><span>Ringkasan</span></h3>
            <div class="summary-grid report-overview-grid">
                <div class="summary-item" title="{{ $summary['student_bills'] }} tagihan, {{ $summary['cooperative'] }} koperasi, {{ $summary['other_income'] }} pemasukan lain"><div class="summary-label">Semua Transaksi</div><div class="summary-value">{{ number_format($summary['transactions'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Nilai Transaksi</div><div class="summary-value">Rp {{ number_format($summary['gross'], 0, ',', '.') }}</div></div>
                <div class="summary-item"><div class="summary-label">Potongan</div><div class="summary-value">Rp {{ number_format($summary['discount'], 0, ',', '.') }}</div></div>
                <div class="summary-item is-total"><div class="summary-label">Total Diterima</div><div class="summary-value">Rp {{ number_format($summary['received'], 0, ',', '.') }}</div></div>
            </div>
        </div>

        <div class="summary-section">
            <h3 class="subtotal-title"><i class="fas fa-wallet" aria-hidden="true"></i><span>Metode bayar</span></h3>
            <div class="summary-grid report-funds-grid">
                @foreach(['tunai' => 'TN / Tunai', 'transfer' => 'TF / Transfer', 'kjc' => 'KJC', 'tabungan' => 'Tabungan', 'belum' => 'Belum dicatat'] as $method => $label)
                    <div class="summary-item" data-payment-method="{{ $method }}"><div class="summary-label">{{ $label }}</div><div class="summary-value">Rp {{ number_format($summary['funds'][$method], 0, ',', '.') }}</div></div>
                @endforeach
            </div>
        </div>
        </section>

        <div class="table-container">
            <div class="table-header"><h3 class="table-title"><i class="fas fa-table"></i> Rincian Semua Transaksi</h3></div>
            <div class="table-responsive">
                @if($transactions->count())
                    <table class="modern-table all-transaction-table" data-server-paginate data-total="{{ $transactions->total() }}" data-per-page="{{ \App\Support\ServerTable::size(request()) }}" data-sort-columns="sort_id,date,reference,party,,gross,discount,received," data-text-sort="party" data-date-sort="date">
                        <colgroup><col style="width:4%"><col style="width:9%"><col style="width:14%"><col style="width:18%"><col style="width:20%"><col style="width:10%"><col style="width:8%"><col style="width:10%"><col style="width:7%"></colgroup>
                        <thead><tr>
                            <th>No</th><th>Tanggal</th><th>Referensi / Jenis</th><th>Siswa / Sumber</th><th>Rincian pembayaran</th><th class="report-money">Nilai</th><th class="report-money">Potongan</th><th class="report-money">Diterima</th><th>Metode</th>
                        </tr></thead>
                        <tbody>
                        @foreach($transactions as $index => $row)
                            <tr>
                                <td>{{ $transactions->firstItem() + $loop->index }}</td>
                                <td>{{ $row['date']->format('d/m/Y') }}</td>
                                <td><strong>{{ $row['reference'] }}</strong><span class="report-note">{{ $row['type_label'] }}</span></td>
                                <td><strong>{{ $row['party'] }}</strong><span class="report-note">{{ $row['school'] }}@if($row['class'] !== '-') · {{ $row['class'] }}@endif</span></td>
                                <td>{{ $row['description'] }}<span class="report-note">{{ number_format($row['item_count'], 0, ',', '.') }} item</span>@if($row['note'])<span class="report-note">Catatan: {{ $row['note'] }}</span>@endif</td>
                                <td class="report-money">Rp {{ number_format($row['gross'], 0, ',', '.') }}</td>
                                <td class="report-money">{{ $row['discount'] > 0 ? 'Rp ' . number_format($row['discount'], 0, ',', '.') : '-' }}</td>
                                <td class="report-money"><strong>Rp {{ number_format($row['received'], 0, ',', '.') }}</strong></td>
                                <td>{{ $row['method'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot><tr>
                            <th colspan="5" class="report-money">Total</th>
                            <th class="report-money">Rp {{ number_format($summary['gross'], 0, ',', '.') }}</th>
                            <th class="report-money">Rp {{ number_format($summary['discount'], 0, ',', '.') }}</th>
                            <th class="report-money">Rp {{ number_format($summary['received'], 0, ',', '.') }}</th><th></th>
                        </tr></tfoot>
                    </table>
                    @include('partials.server-pagination', ['paginator' => $transactions])
                @else
                    <div class="empty-state"><i class="fas fa-inbox"></i><p>Tidak ada transaksi yang sesuai dengan filter.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
