@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
    .main-content {
        margin-left: 280px;
        min-height: 100vh;
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 50%, #a7f3d0 100%);
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
        padding: 2rem 1.5rem; 
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 2.5rem;
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(34, 197, 94, 0.1);
        border: 2px solid rgba(34, 197, 94, 0.1);
    }

    .page-title {
        font-size: 2.2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #14532d, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a, #15803d);
        color: #fff;
        padding: 0.75rem 1.25rem;
        border-radius: 16px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.4s ease;
        display: inline-block;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 30px rgba(34, 197, 94, 0.4);
    }

    .btn-generate {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        padding: 0.75rem 1.25rem;
        border-radius: 16px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.4s ease;
        display: inline-block;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        border: none;
        cursor: pointer;
    }

    .btn-generate:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
    }

    .filter-section {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 15px 35px rgba(34, 197, 94, 0.1);
        border: 2px solid rgba(34, 197, 94, 0.1);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .filter-group label {
        display: block;
        font-weight: 600;
        color: #14532d;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid rgba(34, 197, 94, 0.2);
        border-radius: 12px;
        background: white;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .btn-filter {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .btn-filter-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
    }

    .btn-filter-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
    }

    .btn-filter-secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-filter-secondary:hover {
        background: #e5e7eb;
    }

    .table-container {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(34, 197, 94, 0.1);
        border: 2px solid rgba(34, 197, 94, 0.1);
    }

    .tagihan-table {
        width: 100%;
        border-collapse: collapse;
    }

    .tagihan-table th {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        padding: 1.25rem 1.5rem;
        text-align: left;
        font-weight: 700;
        color: #14532d;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(34, 197, 94, 0.1);
    }

    .tagihan-table td {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(34, 197, 94, 0.05);
        vertical-align: middle;
    }

    .tagihan-table tbody tr {
        transition: all 0.3s ease;
    }

    .tagihan-table tbody tr:hover {
        background: rgba(34, 197, 94, 0.05);
        transform: translateX(5px);
    }

    .student-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .student-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 1rem;
    }

    .student-details {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .tagihan-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .tagihan-name {
        font-weight: 600;
        color: #374151;
    }

    .tagihan-type {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .nominal {
        font-weight: 700;
        color: #dc2626;
        font-size: 1.1rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
        text-align: center;
        min-width: 80px;
    }

    .status-belum {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #dc2626;
        border: 1px solid #fca5a5;
    }

    .status-lunas {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        color: #16a34a;
        border: 1px solid #86efac;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
    }

    .btn-bayar {
        background: linear-gradient(135deg, #25845d, #1d6b4c);
        color: white;
    }

    .btn-bayar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    }

    .btn-hapus {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-hapus:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
        padding: 1.5rem;
    }

    .pagination a,
    .pagination span {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .pagination a {
        background: white;
        color: #374151;
        border: 2px solid rgba(34, 197, 94, 0.2);
    }

    .pagination a:hover {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
    }

    .pagination .active span {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 10px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #d1d5db;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: #374151;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
        }

        .filter-grid {
            grid-template-columns: 1fr;
        }

        .filter-actions {
            flex-direction: column;
        }

        .btn-filter {
            width: 100%;
        }

        .tagihan-table {
            font-size: 0.875rem;
        }

        .tagihan-table th,
        .tagihan-table td {
            padding: 0.75rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-file-invoice-dollar"></i> 
                Daftar Tagihan Siswa
            </h2>
            <div>
                <button type="button" class="btn-generate" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="fas fa-cogs"></i> Generate Otomatis
                </button>
            </div>
        </div>

        <!-- Generate Tagihan Modal -->
        <div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="generateModalLabel">Generate Tagihan Otomatis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin mengenerate tagihan secara otomatis untuk semua siswa? Proses ini akan membuat tagihan baru untuk semua siswa yang memenuhi syarat.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Proses ini mungkin memakan waktu beberapa menit tergantung jumlah data. Harap tunggu sampai proses selesai.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <form action="{{ route('tagihan.generate.manual') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-cogs"></i> Generate Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="{{ route('tagihan.index.original') }}">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="sekolah_id">Filter Sekolah</label>
                        <select name="sekolah_id" id="sekolah_id" class="form-select">
                            <option value="">Semua Sekolah</option>
                            @foreach($sekolah as $s)
                                <option value="{{ $s->id }}" {{ $selectedSekolah == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama_sekolah }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="kelas_id">Filter Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id }}" {{ $selectedKelas == $k->id ? 'selected' : '' }}>
                                    {{ $k->tingkat }} - {{ $k->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="search">Cari Siswa/Tagihan</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Nama siswa, NIS, atau nama tagihan..."
                               value="{{ $search }}" class="form-control">
                    </div>
                </div>

                <div class="filter-actions">
                    <a href="{{ route('tagihan.index.original') }}" class="btn-filter btn-filter-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <button type="submit" class="btn-filter btn-filter-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            @if($tagihan->count() > 0)
                <table class="tagihan-table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Tagihan</th>
                            <th>Nominal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihan as $t)
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <div class="student-name">{{ $t->siswa->nama }}</div>
                                        <div class="student-details">
                                            NIS: {{ $t->siswa->nis }} | 
                                            {{ $t->siswa->kelas->tingkat }} - {{ $t->siswa->kelas->nama_kelas }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="tagihan-info">
                                        <div class="tagihan-name">{{ $t->nama_tagihan }}</div>
                                        <div class="tagihan-type">
                                            Tipe: {{ ucfirst($t->tipe) }} | Periode: {{ $t->periode }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="nominal">Rp {{ number_format($t->nominal, 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    {{ $t->tanggal_jatuh_tempo ? $t->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $t->status }}">
                                        {{ ucfirst($t->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        @if($t->status == 'belum')
                                            @if($t->tipe == 'bulanan')
                                                <a href="{{ route('tagihan.bayar', $t->id) }}" 
                                                   class="btn-action btn-bayar">
                                                    <i class="fas fa-credit-card"></i> Bayar
                                                </a>
                                            @elseif($t->tipe == 'sekali')
                                                <a href="{{ route('tagihan.bayarSekali', $t->id) }}" 
                                                   class="btn-action btn-bayar">
                                                    <i class="fas fa-credit-card"></i> Bayar
                                                </a>
                                            @elseif($t->tipe == 'tahunan' || $t->tipe == 'setahun')
                                                <a href="{{ route('tagihan.bayarTahunan', $t->id) }}" 
                                                   class="btn-action btn-bayar">
                                                    <i class="fas fa-credit-card"></i> Bayar
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('tagihan.bayar', $t->id) }}" 
                                               class="btn-action btn-bayar">
                                                <i class="fas fa-history"></i> Riwayat
                                            </a>
                                        @endif
                                        
                                        <form action="{{ route('tagihan.destroy', $t->id) }}" 
                                              method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action btn-hapus"
                                                    data-confirm-title="Hapus tagihan" data-confirm-message="Tagihan ini akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    {{ $tagihan->links() }}
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak Ada Data Tagihan</h3>
                    <p>Belum ada tagihan yang sesuai dengan filter Anda.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
