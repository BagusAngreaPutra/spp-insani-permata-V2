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
    .page-header, .filter-form, .table-container {
        background: rgba(255,255,255,0.94);
        border: 1px solid rgba(34,197,94,0.12);
        box-shadow: 0 18px 38px rgba(15,23,42,0.09);
    }
    .page-header {
        align-items: center;
        border-radius: 22px;
        display: flex;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        padding: 2rem;
    }
    .page-title { align-items: center; color: #14532d; display: flex; font-size: 2rem; font-weight: 800; gap: 0.75rem; margin: 0; }
    .btn-primary, .btn-secondary {
        border-radius: 12px;
        color: #fff;
        display: inline-flex;
        font-weight: 700;
        gap: 0.5rem;
        padding: 0.75rem 1.2rem;
        text-decoration: none;
    }
    .btn-primary { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .btn-secondary { background: linear-gradient(135deg, #64748b, #475569); }
    .filter-form { align-items: flex-end; border-radius: 18px; display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; }
    .form-group { display: flex; flex-direction: column; min-width: 210px; }
    .form-group label { color: #166534; font-size: 0.9rem; font-weight: 700; margin-bottom: 0.45rem; }
    .form-group input, .form-group select { border: 2px solid rgba(34,197,94,0.18); border-radius: 12px; padding: 0.7rem 0.9rem; }
    .filter-btn, .reset-btn { border: none; border-radius: 12px; color: #fff; font-weight: 700; height: 44px; padding: 0 1rem; text-decoration: none; }
    .filter-btn { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .reset-btn { align-items: center; background: linear-gradient(135deg, #64748b, #475569); display: inline-flex; }
    .table-container { border-radius: 22px; overflow: hidden; }
    .modern-table { border-collapse: collapse; font-size: 0.93rem; table-layout: fixed; width: 100%; }
    .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 54px; }
    .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 17%; }
    .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 20%; }
    .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 17%; }
    .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 10%; }
    .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 14%; }
    .modern-table th:nth-child(7), .modern-table td:nth-child(7) { width: 190px; }
    .modern-table th, .modern-table td { border-bottom: 1px solid rgba(220,252,231,0.8); padding: 1rem 1.1rem; text-align: left; vertical-align: top; }
    .modern-table th { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #166534; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; }
    .btn-view, .btn-delete { border: none; border-radius: 8px; color: #fff; display: inline-flex; font-weight: 700; line-height: 1; min-height: 32px; padding: 0.48rem 0.65rem; text-decoration: none; white-space: nowrap; }
    .btn-view { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .btn-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .koperasi-actions { align-items: center; display: inline-flex; flex-wrap: nowrap; gap: 0.35rem; max-width: 100%; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
    .koperasi-actions > * { flex: 0 0 auto; }
    .koperasi-actions form { display: inline-flex !important; margin: 0; }
    .alert-success { background: #dcfce7; border-radius: 14px; color: #166534; margin-bottom: 1rem; padding: 1rem 1.25rem; }
    .pagination-wrapper { display: flex; justify-content: flex-end; margin-top: 1.5rem; }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @include('partials.admin-page-context', [
            'section' => 'Koperasi',
            'current' => 'Penjualan',
            'title' => 'Catat pembelian siswa di koperasi sekolah.',
            'description' => 'Setiap transaksi menyimpan data siswa pembeli dan barang yang dibeli, lalu stok barang berkurang otomatis.',
            'steps' => ['Pilih siswa', 'Pilih barang', 'Simpan transaksi']
        ])

        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-cash-register"></i> Penjualan Koperasi</h2>
            <div>
                <a href="{{ route('koperasi.index') }}" class="btn-secondary"><i class="fas fa-boxes-stacked"></i> Stok Barang</a>
                <a href="{{ route('koperasi.penjualan.create') }}" class="btn-primary"><i class="fas fa-plus"></i> Transaksi Baru</a>
            </div>
        </div>

        @include('partials.transaction-tabs', ['module' => 'koperasi', 'active' => 'data'])

        <form method="GET" action="{{ route('koperasi.penjualan.index') }}" class="filter-form">
            <div class="form-group">
                <label for="sekolah_id">Sekolah</label>
                <select name="sekolah_id" id="sekolah_id">
                    <option value="">Semua Sekolah</option>
                    @foreach($sekolah as $item)
                        <option value="{{ $item->id }}" {{ $selectedSekolah == $item->id ? 'selected' : '' }}>{{ $item->nama_sekolah }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="search">Cari Transaksi / Siswa</label>
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Kode, NIS, atau nama siswa...">
            </div>
            <button type="submit" class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
            <a href="{{ route('koperasi.penjualan.index') }}" class="reset-btn"><i class="fas fa-undo"></i> Reset</a>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Transaksi</th>
                        <th>Siswa</th>
                        <th>Sekolah</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penjualan as $item)
                        <tr>
                            <td>{{ $loop->iteration + ($penjualan->currentPage() - 1) * $penjualan->perPage() }}</td>
                            <td>
                                <strong>{{ $item->kode_transaksi }}</strong><br>
                                <small>{{ optional($item->tanggal)->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <strong>{{ $item->siswa->nama ?? '-' }}</strong><br>
                                <small>{{ $item->siswa->nis ?? '-' }} | {{ $item->siswa->kelas->kelas ?? '-' }}</small>
                            </td>
                            <td>{{ $item->sekolah->nama_sekolah ?? '-' }}</td>
                            <td>{{ $item->details->sum('jumlah') }} barang</td>
                            <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                            <td>
                                <div class="koperasi-actions">
                                    <a href="{{ route('koperasi.penjualan.show', $item->id) }}" class="btn-view">Detail</a>
                                    <a href="{{ route('koperasi.penjualan.kwitansi', $item->id) }}" target="_blank" class="btn-view" style="background: linear-gradient(135deg, #25845d, #1d6b4c);">Kwitansi</a>
                                    <form action="{{ route('koperasi.penjualan.destroy', $item->id) }}" method="POST" data-confirm-title="Batalkan transaksi koperasi" data-confirm-message="Transaksi {{ $item->kode_transaksi }} akan dibatalkan dan stok barang dikembalikan." data-confirm-label="Batalkan transaksi" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-delete">Batal</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('partials.admin-empty-state', [
                                    'icon' => 'fas fa-cash-register',
                                    'title' => 'Belum Ada Penjualan Koperasi',
                                    'message' => 'Catat pembelian siswa agar stok barang berkurang otomatis dan riwayat penjualan tersimpan.',
                                    'actionRoute' => route('koperasi.penjualan.create'),
                                    'actionText' => 'Buat Transaksi Pertama'
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">{{ $penjualan->links() }}</div>
    </div>
</div>
@endsection
