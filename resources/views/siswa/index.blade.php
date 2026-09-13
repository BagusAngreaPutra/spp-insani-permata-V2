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
    .filter-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .filter-form .form-group {
        display: flex;
        flex-direction: column;
    }
    .filter-form label {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .filter-form select,
    .filter-form input,
    .filter-form button {
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
    .filter-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(34,197,94,0.3);
        height: 42px;
    }
    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34,197,94,0.4);
    }

    .table-container {
        background: rgba(255, 255, 255, 0.95);
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
        box-shadow: 0 4px 12px rgba(34,197,94,0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34,197,94,0.4);
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .btn-detail {
        background: linear-gradient(135deg, #25845d, #1d6b4c);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .btn-detail:hover {
        background: linear-gradient(135deg, #1d6b4c, #103e2d);
        transform: translateY(-1px);
        color: #fff;
        text-decoration: none;
    }

    .btn-edit {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .btn-edit:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        transform: translateY(-1px);
        color: #fff;
        text-decoration: none;
    }

    .btn-delete {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .btn-delete:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-1px);
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

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.25rem;
    }
    .badge.bg-success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }

    .empty-cell {
        color: #9ca3af;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 0.25rem;
        }
        .modern-table {
            font-size: 0.875rem;
        }
        .modern-table th,
        .modern-table td {
            padding: 0.75rem 0.5rem;
        }
    }
     /* === FILTER FORM === */
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
    }
    .filter-form .form-group {
        display: flex;
        flex-direction: column;
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
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(34,197,94,0.3);
        height: 48px;
        padding: 0 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34,197,94,0.4);
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
            'current' => 'Siswa',
            'title' => 'Data siswa adalah dasar pembuatan tagihan dan pembayaran.',
            'description' => 'Tambahkan siswa setelah sekolah, tahun ajaran, dan kelas sudah tersedia. Gunakan filter untuk memeriksa siswa per sekolah atau kelas.',
            'steps' => ['Sekolah', 'Tahun Ajaran', 'Kelas', 'Siswa', 'Tagihan']
        ])

        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-users"></i> Daftar Siswa
            </h2>
            <a href="{{ route('siswa.create') }}" class="btn-primary">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
        </div>

        {{-- Form Filter --}}
        <form action="{{ route('siswa.index') }}" method="GET" class="filter-form">
            <div class="form-group">
                <label for="sekolah_id">Pilih Sekolah</label>
                <select name="sekolah_id" id="sekolah_id">
                    <option value="">Semua Sekolah</option>
                    @foreach($sekolah as $s)
                        <option value="{{ $s->id }}" {{ (isset($selectedSekolah) && $selectedSekolah==$s->id) ? 'selected' : '' }}>
                            {{ $s->nama_sekolah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="kelas_id">Pilih Kelas</label>
                <select name="kelas_id"
                        id="kelas_id"
                        data-class-filter-for="sekolah_id"
                        data-all-label="Semua Kelas">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $k)
                        @php
                            $className = trim((string) $k->nama_kelas);
                            $classLabel = in_array($className, ['', '-', '–'], true)
                                ? $k->label_tingkat
                                : $k->label_tingkat.' · '.$className;
                        @endphp
                        <option value="{{ $k->id }}"
                                data-school-id="{{ $k->sekolah_id }}"
                                data-school-name="{{ $k->sekolah?->nama_sekolah }}"
                                data-class-label="{{ $classLabel }}"
                                {{ (isset($selectedKelas) && $selectedKelas==$k->id) ? 'selected' : '' }}>
                            {{ $classLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="search">Cari (NIS / Nama)</label>
                <input type="text" name="search" id="search" value="{{ $search ?? '' }}" placeholder="Ketik kata kunci...">
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ route('siswa.index') }}" class="reset-btn">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Sekolah</th>
                        <th style="width: 180px;">Kelas</th>
                        <th>Tahun Ajaran</th>
                        <th>Nominal SPP</th>
                        <th style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nis }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->sekolah->nama_sekolah ?? '-' }}</td>
                            <td>
                                @if($item->kelas)
                                    {{ $item->kelas->label_tingkat }}
                                    @if(!empty($item->kelas->nama_kelas))
                                        {{ $item->kelas->nama_kelas }}
                                    @endif
                                @else
                                    <span class="empty-cell">-</span>
                                @endif
                            </td>
                            <td>
                                @if($item->tahunAjaran)
                                    {{ $item->tahunAjaran->nama_tahun }}
                                    @if($item->tahunAjaran->aktif)
                                        <span class="badge bg-success">Aktif</span>
                                    @endif
                                @else
                                    <span class="empty-cell">-</span>
                                @endif
                            </td>
                            <td>Rp {{ number_format($item->nominal_spp,0,',','.') }}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('siswa.show', $item->id) }}" class="btn-detail">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <a href="{{ route('siswa.edit', $item->id) }}" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm-title="Hapus siswa" data-confirm-message="Data siswa {{ $item->nama }} akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-delete">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                @include('partials.admin-empty-state', [
                                    'icon' => 'fas fa-user-graduate',
                                    'title' => 'Belum Ada Data Siswa',
                                    'message' => 'Tambahkan siswa setelah memilih sekolah, kelas, dan tahun ajaran. Setelah siswa tersedia, admin bisa membuat tagihan.',
                                    'actionRoute' => route('siswa.create'),
                                    'actionText' => 'Tambah Siswa Pertama'
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination jika ada --}}
        @if(method_exists($siswa, 'links'))
            <div class="mt-4">
                {{ $siswa->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
