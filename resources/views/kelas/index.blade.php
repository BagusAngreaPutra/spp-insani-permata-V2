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
    .content-area { padding: 3rem 2.5rem; }

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
    }
    .filter-form select:focus,
    .filter-form input:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        transform: translateY(-1px);
    }
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
    }
    .filter-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    .filter-btn:hover::before { left: 100%; }
    .filter-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 30px rgba(34, 197, 94, 0.4);
    }
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
    }
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }
    .modern-table th, .modern-table td {
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
    .btn-primary {
        background: linear-gradient(135deg,  #22c55e, #16a34a);
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
    .btn-edit:hover { background: #d97706; }
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
    .btn-delete:hover { background: #dc2626; }
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
        background: linear-gradient(135deg, #2d3748, #4a5568);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #bbf7d0);
        border: 1px solid rgba(34,197,94,0.2);
        color: #166534;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 1.5rem;
    }
    .pagination-wrapper {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }
    .pagination-wrapper .pagination {
        gap: 0.4rem;
        margin-bottom: 0;
        flex-wrap: wrap;
    }
    .pagination-wrapper .page-link {
        border: 1px solid rgba(34, 197, 94, 0.18);
        border-radius: 10px;
        color: #166534;
        font-weight: 600;
        min-width: 40px;
        padding: 0.55rem 0.8rem;
        text-align: center;
        box-shadow: 0 6px 14px rgba(34, 197, 94, 0.08);
    }
    .pagination-wrapper .page-link:hover {
        background: #dcfce7;
        border-color: rgba(34, 197, 94, 0.35);
        color: #14532d;
    }
    .pagination-wrapper .page-item.active .page-link {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-color: #16a34a;
        color: #fff;
    }
    .pagination-wrapper .page-item.disabled .page-link {
        background: #f8fafc;
        color: #94a3b8;
        box-shadow: none;
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

        @include('partials.admin-page-context', [
            'section' => 'Master Data',
            'current' => 'Kelas',
            'title' => 'Data kelas dipakai saat input siswa dan proses tagihan.',
            'description' => 'Pastikan sekolah dan tahun ajaran sudah dibuat sebelum menambahkan kelas, supaya admin tidak memilih data yang kosong.',
            'steps' => ['Sekolah', 'Tahun Ajaran', 'Kelas', 'Siswa']
        ])

        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-chalkboard"></i> Kelas
            </h2>
            <a href="{{ route('kelas.create') }}" class="btn-primary">
                Tambah Kelas
            </a>
        </div>

        {{-- 🔎 Form Filter Kelas --}}
        <form method="GET" action="{{ route('kelas.index') }}" class="filter-form">
            <div class="form-group">
                <label for="sekolah_id">Pilih Sekolah</label>
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
                <label for="search">Cari (Nama Kelas / Tingkat)</label>
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Ketik kata kunci...">
            </div>
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ route('kelas.index') }}" class="reset-btn">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Sekolah</th>
                        <th>Tahun Ajaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelas as $item)
                        <tr>
                            <td>{{ $loop->iteration + ($kelas->currentPage() - 1) * $kelas->perPage() }}</td>
                            <td>{{ $item->nama_kelas ?: '' }}</td>
                            <td>{{ $item->label_tingkat }}</td>
                            <td>{{ $item->sekolah->nama_sekolah ?? '-' }}</td>
                            <td>{{ $item->tahunAjaran->nama_tahun ?? '-' }}</td>
                            <td class="flex gap-2">
                                <a href="{{ route('kelas.edit', $item->id) }}" class="btn-edit">Edit</a>
                                <form action="{{ route('kelas.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus kelas" data-confirm-message="Kelas ini akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                @include('partials.admin-empty-state', [
                                    'icon' => 'fas fa-chalkboard',
                                    'title' => 'Belum Ada Data Kelas',
                                    'message' => 'Tambahkan kelas setelah data sekolah dan tahun ajaran tersedia. Kelas akan dipakai saat membuat data siswa.',
                                    'actionRoute' => route('kelas.create'),
                                    'actionText' => 'Tambah Kelas Pertama'
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            {{ $kelas->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
