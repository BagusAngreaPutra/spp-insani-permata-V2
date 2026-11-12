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
    .detail-card {
        background: rgba(255,255,255,0.95);
        border: 1px solid rgba(34,197,94,0.12);
        border-radius: 22px;
        box-shadow: 0 18px 38px rgba(15,23,42,0.09);
        padding: 2rem;
    }
    .page-title { color: #14532d; font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; }
    .summary-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 1.5rem; }
    .summary-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 1rem; }
    .summary-box small { color: #166534; display: block; font-weight: 700; margin-bottom: 0.35rem; }
    .summary-box strong { color: #14532d; }
    .items-table { border-collapse: collapse; width: 100%; }
    .items-table th, .items-table td { border-bottom: 1px solid #dcfce7; padding: 0.9rem; text-align: left; }
    .items-table th { color: #166534; font-size: 0.82rem; text-transform: uppercase; }
    .total-row td { color: #14532d; font-size: 1.05rem; font-weight: 800; }
    .actions { margin-top: 1.5rem; }
    .btn-back, .btn-print, .btn-delete {
        border: none;
        border-radius: 12px;
        color: #fff;
        font-weight: 800;
        padding: 0.75rem 1.2rem;
        text-decoration: none;
    }
    .btn-back { background: linear-gradient(135deg, #64748b, #475569); }
    .btn-print { background: linear-gradient(135deg, #25845d, #1d6b4c); }
    .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .alert-success { background: #dcfce7; border-radius: 14px; color: #166534; margin-bottom: 1rem; padding: 1rem 1.25rem; }
    @media (max-width: 768px) {
        .summary-grid { grid-template-columns: 1fr; }
        .items-table { display: block; overflow-x: auto; }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())<div class="alert-success" role="alert">{{ $errors->first() }}</div>@endif

        @include('partials.admin-page-context', [
            'section' => 'Koperasi',
            'current' => 'Detail Penjualan',
            'title' => 'Detail transaksi penjualan koperasi.',
            'description' => 'Halaman ini menampilkan siswa pembeli dan rincian barang yang keluar dari stok koperasi.'
        ])

        <div class="detail-card">
            <h2 class="page-title"><i class="fas fa-receipt"></i> {{ $penjualan->kode_transaksi }}</h2>

            <div class="summary-grid">
                <div class="summary-box">
                    <small>Tanggal</small>
                    <strong>{{ optional($penjualan->tanggal)->format('d/m/Y') }}</strong>
                </div>
                <div class="summary-box">
                    <small>Siswa</small>
                    <strong>{{ $penjualan->siswa->nama ?? '-' }}</strong><br>
                    {{ $penjualan->siswa->nis ?? '-' }} | {{ $penjualan->siswa->kelas->kelas ?? '-' }}
                </div>
                <div class="summary-box">
                    <small>Sekolah</small>
                    <strong>{{ $penjualan->sekolah->nama_sekolah ?? '-' }}</strong>
                </div>
                <div class="summary-box">
                    <small>Metode pembayaran</small>
                    <strong>{{ match($penjualan->metode_bayar) { 'tunai' => 'Tunai (TN)', 'transfer' => 'Transfer (TF)', default => 'Belum dicatat' } }}</strong>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($penjualan->details as $detail)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $detail->nama_barang }}</strong><br>
                                <small>{{ $detail->barang->kode_barang ?? '-' }}</small>
                            </td>
                            <td>{{ $detail->jumlah }}</td>
                            <td>Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4">Total</td>
                        <td>Rp {{ number_format($penjualan->total, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            @if($penjualan->catatan)
                <div class="summary-box" style="margin-top: 1rem;">
                    <small>Catatan</small>
                    {{ $penjualan->catatan }}
                </div>
            @endif

            <div class="actions transaction-actions">
                <a href="{{ route('koperasi.penjualan.index') }}" class="transaction-action"><i class="fas fa-arrow-left" aria-hidden="true"></i>Kembali</a>
                <a href="{{ route('koperasi.penjualan.kwitansi', $penjualan->id) }}" target="_blank" class="transaction-action"><i class="fas fa-print" aria-hidden="true"></i>Cetak Kwitansi</a>
                <a href="{{ route('koperasi.penjualan.edit', $penjualan->id) }}" class="transaction-action"><i class="fas fa-pen" aria-hidden="true"></i>Koreksi Transaksi</a>
                <form action="{{ route('koperasi.penjualan.destroy', $penjualan->id) }}" method="POST" data-confirm-reason data-confirm-title="Batalkan transaksi koperasi" data-confirm-message="Transaksi ini akan dibatalkan dan stok barang dikembalikan." data-confirm-label="Batalkan transaksi" data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="alasan_pembatalan" value="">
                    <button type="submit" class="transaction-action is-danger btn-danger"><i class="fas fa-trash-can" aria-hidden="true"></i>Batalkan Transaksi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
