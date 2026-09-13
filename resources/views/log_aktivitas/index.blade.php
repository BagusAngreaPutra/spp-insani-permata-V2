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
        vertical-align: middle;
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

    .btn-detail {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-detail:hover {
        background: #16a34a;
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
        background: #503a3aff;
    }

    .search-form {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .search-form input {
        padding: 0.65rem 1rem;
        border-radius: 12px;
        border: 1px solid #d1d5db;
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-history"></i> Riwayat Aktivitas</h2>
            <form action="{{ route('log_aktivitas.index') }}" method="GET" class="search-form">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aktivitas...">
                <select name="aktor_type" class="form-control">
                    <option value="">Semua</option>
                    <option value="admin" {{ request('aktor_type')=='admin' ? 'selected' : '' }}>Admin</option>
                    <option value="siswa" {{ request('aktor_type')=='siswa' ? 'selected' : '' }}>Siswa</option>
                </select>
                <button class="btn-detail"><i class="fas fa-search"></i> Cari</button>
            </form>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>Aktor</th>
                        <th>Tipe Aktor</th>
                        <th>Aktivitas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logAktivitas as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                @if($log->aktor_type === 'admin')
                                    {{ $log->admin?->nama_admin ?? '-' }}
                                @else
                                    {{ $log->siswa?->nama ?? '-' }}
                                @endif
                            </td>
                            <td>{{ ucfirst($log->aktor_type) }}</td>
                            <td>{{ Str::limit($log->aktivitas, 50) }}</td>
                            <td style="display:flex; gap:0.5rem;">
                                <a href="{{ route('log_aktivitas.show', $log->id) }}" class="btn-detail">Detail</a>

                                <form action="{{ route('log_aktivitas.destroy', $log->id) }}" method="POST"
                                      data-confirm-title="Hapus aktivitas" data-confirm-message="Riwayat aktivitas ini akan dihapus." data-confirm-label="Hapus" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-4">Belum ada aktivitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logAktivitas->links() }}
        </div>

        {{-- Tombol Hapus Semua Riwayat --}}
        <div class="mt-5">
            <form action="{{ route('log_aktivitas.destroyAll') }}" method="POST"
                  data-confirm-title="Hapus semua aktivitas" data-confirm-message="Semua riwayat aktivitas akan dihapus dan tindakan ini tidak dapat dibatalkan." data-confirm-label="Hapus semua" data-confirm-tone="danger">
                @csrf
                @method('DELETE')
                <button type="submit"
                        style="background: linear-gradient(135deg,#b91c1c,#7f1d1d); color:#fff; padding:0.75rem 1.5rem; border-radius:12px; font-weight:bold; border:none; cursor:pointer;">
                    <i class="fas fa-trash-alt"></i> Hapus Semua Riwayat Aktivitas
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
