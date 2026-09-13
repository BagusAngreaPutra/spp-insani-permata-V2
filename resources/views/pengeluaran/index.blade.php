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
            top: 0;
            right: auto;
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

    .btn-edit {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: 0.3s;
    }

    .btn-edit:hover {
        background: #d97706;
    }

    .btn-delete {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-delete:hover {
        background: #dc2626;
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

    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #bbf7d0);
        border: 1px solid rgba(34, 197, 94, 0.2);
        color: #166534;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-file-invoice-dollar"></i> Pengeluaran
            </h2>
            <a href="{{ route('pengeluaran.create') }}" class="btn-primary">
                Tambah Pengeluaran
            </a>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Keperluan</th>
                        <th>Sekolah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pengeluaran as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                            <td>Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                            <td>{{ $item->keperluan ?? '-' }}</td>
                            <td>{{ $item->sekolah->nama_sekolah ?? '-' }}</td>
                            <td class="flex gap-2">
                                <a href="{{ route('pengeluaran.edit', $item->id) }}" class="btn-edit">Edit</a>
                                <form action="{{ route('pengeluaran.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus pengeluaran" data-confirm-message="Data pengeluaran ini akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Belum ada data pengeluaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pengeluaran->links() }}
        </div>
    </div>
</div>
@endsection
