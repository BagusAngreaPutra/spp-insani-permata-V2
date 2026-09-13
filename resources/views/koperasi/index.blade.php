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

    .page-header,
    .filter-form,
    .table-container {
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

    .page-title {
        align-items: center;
        color: #14532d;
        display: flex;
        font-size: 2rem;
        font-weight: 800;
        gap: 0.75rem;
        margin: 0;
    }

    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 12px;
        color: #fff;
        display: inline-flex;
        font-weight: 700;
        gap: 0.5rem;
        padding: 0.75rem 1.2rem;
        text-decoration: none;
    }

    .page-actions .btn-sales {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.55rem !important;
        min-height: 42px !important;
        padding: 0.45rem 0.95rem !important;
        color: #fff !important;
        background: linear-gradient(135deg, #06b6d4, #2563eb) !important;
        border: 1px solid #1677d2 !important;
        border-radius: 9px !important;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2) !important;
        font-weight: 750 !important;
        text-decoration: none !important;
    }

    .page-actions .btn-sales:hover {
        color: #fff !important;
        background: linear-gradient(135deg, #0891b2, #1d4ed8) !important;
        transform: translateY(-1px);
    }

    .page-actions .btn-sales i {
        display: grid !important;
        place-items: center !important;
        width: 25px !important;
        height: 25px !important;
        color: #fff !important;
        background: rgba(255, 255, 255, 0.17) !important;
        border: 1px solid rgba(255, 255, 255, 0.24) !important;
        border-radius: 7px !important;
        font-size: 0.78rem !important;
        line-height: 1 !important;
    }

    .filter-form {
        align-items: flex-end;
        border-radius: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        min-width: 190px;
    }

    .form-group label {
        color: #166534;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .form-group input,
    .form-group select {
        border: 2px solid rgba(34,197,94,0.18);
        border-radius: 12px;
        padding: 0.7rem 0.9rem;
    }

    .filter-btn,
    .reset-btn {
        border: none;
        border-radius: 12px;
        color: #fff;
        font-weight: 700;
        height: 44px;
        padding: 0 1rem;
        text-decoration: none;
    }

    .filter-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
    }

    .reset-btn {
        align-items: center;
        background: linear-gradient(135deg, #64748b, #475569);
        display: inline-flex;
    }

    .table-container {
        border-radius: 22px;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .modern-table {
        border-collapse: collapse;
        font-size: 0.93rem;
        table-layout: fixed;
        width: 100%;
        min-width: 980px;
    }

    /* Kolom nomor tetap ringkas, tetapi beri ruang baca sedikit lebih lega. */
    .modern-table th:nth-child(1),
    .modern-table td:nth-child(1) {
        width: 60px;
        min-width: 60px;
        max-width: 60px;
        text-align: center;
        padding-left: 0.5rem;
        padding-right: 1rem;
    }

    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 22%;
    }

    .modern-table th:nth-child(3),
    .modern-table td:nth-child(3) {
        width: 13%;
    }

    .modern-table th:nth-child(4),
    .modern-table td:nth-child(4) {
        width: 17%;
    }

    .modern-table th:nth-child(5),
    .modern-table td:nth-child(5) {
        width: 13%;
    }

    .modern-table th:nth-child(6),
    .modern-table td:nth-child(6) {
        width: 13%;
    }

    /* Kolom Aksi dibuat lebih besar */
    .modern-table th:nth-child(7),
    .modern-table td:nth-child(7) {
        width: 220px;
        min-width: 220px;
    }

    .modern-table th,
    .modern-table td {
        border-bottom: 1px solid rgba(220,252,231,0.8);
        padding: 1rem 1.1rem;
        text-align: left;
        vertical-align: top;
    }

    .modern-table th {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        color: #166534;
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.78rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
    }

    .badge-aman {
        background: #dcfce7;
        color: #166534;
    }

    .badge-menipis {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-habis {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-nonaktif {
        background: #e2e8f0;
        color: #475569;
    }

    .koperasi-stock-display {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .koperasi-stock-amount {
        color: #101828;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .koperasi-stock-display .badge {
        align-items: center;
        gap: 0.3rem;
    }

    .koperasi-stock-display .badge i {
        font-size: 0.7rem;
    }

    .btn-stock,
    .btn-edit,
    .btn-delete {
        border: none;
        border-radius: 8px;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.38rem;
        font-weight: 700;
        line-height: 1;
        min-height: 32px;
        min-width: 58px;
        padding: 0.48rem 0.65rem;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-edit {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .btn-stock {
        color: #fff !important;
        background: linear-gradient(135deg, #06b6d4, #2878e8) !important;
        border-color: #167bc7 !important;
        box-shadow: 0 5px 12px rgba(22, 123, 199, 0.2) !important;
    }

    .btn-stock:hover {
        color: #fff !important;
        background: linear-gradient(135deg, #0891b2, #1d5fd1) !important;
    }

    .koperasi-actions :is(.btn-stock, .btn-edit, .btn-delete) i {
        font-size: 0.78rem;
        line-height: 1;
    }

    .btn-delete {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        cursor: pointer;
    }

    .koperasi-actions {
        align-items: center;
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.45rem;
        max-width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .koperasi-actions > * {
        flex: 0 0 auto;
    }

    .koperasi-actions form {
        display: inline-flex !important;
        margin: 0;
    }

    .alert-success {
        background: #dcfce7;
        border-radius: 14px;
        color: #166534;
        margin-bottom: 1rem;
        padding: 1rem 1.25rem;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }
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
            'current' => 'Stok Barang',
            'title' => 'Kelola stok barang koperasi sekolah.',
            'description' => 'Gunakan halaman ini untuk menambah, mengurangi, mengedit data barang, dan memantau stok sebelum dicatat di penjualan.',
            'steps' => ['Tambah barang', 'Sesuaikan stok', 'Catat penjualan']
        ])

        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-boxes-stacked"></i> Stok Barang Koperasi
            </h2>

            <div class="page-actions">
                <a href="{{ route('koperasi.penjualan.index') }}" class="btn btn-sales">
                    <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                    <span>Penjualan</span>
                </a>

                <a href="{{ route('koperasi.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Barang
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('koperasi.index') }}" class="filter-form">
            <div class="form-group">
                <label for="sekolah_id">Sekolah</label>
                <select name="sekolah_id" id="sekolah_id">
                    <option value="">Semua Sekolah</option>
                    @foreach($sekolah as $item)
                        <option value="{{ $item->id }}" {{ $selectedSekolah == $item->id ? 'selected' : '' }}>
                            {{ $item->nama_sekolah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="kategori">Kategori</label>
                <select name="kategori" id="kategori">
                    <option value="">Semua Kategori</option>
                    @foreach($kategoriList as $key => $label)
                        <option value="{{ $key }}" {{ $selectedKategori == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status Barang</label>
                <select name="status" id="status">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ $selectedStatus == 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ $selectedStatus == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status_stok">Status Stok</label>
                <select name="status_stok" id="status_stok">
                    <option value="">Semua Status Stok</option>
                    <option value="aman" {{ $selectedStatusStok === 'aman' ? 'selected' : '' }}>Aman</option>
                    <option value="menipis" {{ $selectedStatusStok === 'menipis' ? 'selected' : '' }}>Menipis</option>
                    <option value="habis" {{ $selectedStatusStok === 'habis' ? 'selected' : '' }}>Habis</option>
                </select>
            </div>

            <div class="form-group">
                <label for="jenis_transaksi">Jenis Transaksi</label>
                <select name="jenis_transaksi" id="jenis_transaksi">
                    <option value="">Semua Jenis</option>
                    <option value="jual" {{ $selectedJenisTransaksi == 'jual' ? 'selected' : '' }}>Dijual</option>
                    <option value="sewa" {{ $selectedJenisTransaksi == 'sewa' ? 'selected' : '' }}>Disewakan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="search">Cari Barang</label>
                <input
                    type="text"
                    name="search"
                    id="search"
                    value="{{ $search }}"
                    placeholder="Kode / nama barang..."
                >
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>

            <a href="{{ route('koperasi.index') }}" class="reset-btn">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th>Sekolah</th>
                        <th>Harga / Tarif</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($koperasi as $item)
                        <tr>
                            <td>
                                {{ $loop->iteration + ($koperasi->currentPage() - 1) * $koperasi->perPage() }}
                            </td>

                            <td>
                                <strong>{{ $item->nama_barang }}</strong><br>
                                <small>{{ $item->kode_barang ?: '-' }}</small>
                            </td>

                            <td>
                                {{ $kategoriList[$item->kategori] ?? ucfirst($item->kategori) }}
                            </td>

                            <td>
                                {{ $item->jenis_transaksi === 'sewa' ? 'Sewa' : 'Jual' }}
                            </td>

                            <td>
                                {{ $item->sekolah->nama_sekolah ?? '-' }}
                            </td>

                            <td>
                                @if($item->jenis_transaksi === 'sewa')
                                    <strong>PIIS:</strong> Rp {{ number_format($item->harga_sewa_siswa ?? 0, 0, ',', '.') }}<br>
                                    <small>Umum: Rp {{ number_format($item->harga_sewa_umum ?? 0, 0, ',', '.') }}</small>
                                @else
                                    Rp {{ number_format($item->harga_jual, 0, ',', '.') }}
                                @endif
                            </td>

                            <td>
                                @php
                                    $stockIcon = match($item->status_stok) {
                                        'aman' => 'fa-circle-check',
                                        'menipis' => 'fa-triangle-exclamation',
                                        default => 'fa-circle-xmark',
                                    };
                                @endphp
                                <div class="koperasi-stock-display">
                                    <span class="koperasi-stock-amount">{{ $item->stok }} {{ $item->satuan }}</span>
                                    <span class="badge badge-{{ $item->status_stok }}">
                                        <i class="fas {{ $stockIcon }}" aria-hidden="true"></i>
                                        {{ ucfirst($item->status_stok) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="koperasi-actions">
                                    <a
                                        href="{{ route('koperasi.stok.edit', $item->id) }}"
                                        class="btn-stock"
                                    >
                                        <i class="fas fa-boxes-stacked" aria-hidden="true"></i>
                                        Stok
                                    </a>

                                    <a href="{{ route('koperasi.edit', $item->id) }}" class="btn-edit">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        Edit
                                    </a>

                                    <form
                                        action="{{ route('koperasi.destroy', $item->id) }}"
                                        method="POST"
                                        data-confirm-title="Hapus barang koperasi" data-confirm-message="Barang {{ $item->nama_barang }} akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @include('partials.admin-empty-state', [
                                    'icon' => 'fas fa-store',
                                    'title' => 'Belum Ada Barang Koperasi',
                                    'message' => 'Tambahkan buku, seragam, alat tulis, atau barang koperasi lain agar tidak tercampur dengan jenis pembayaran sekolah.',
                                    'actionRoute' => route('koperasi.create'),
                                    'actionText' => 'Tambah Barang Pertama'
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            {{ $koperasi->links() }}
        </div>
    </div>
</div>
@endsection
