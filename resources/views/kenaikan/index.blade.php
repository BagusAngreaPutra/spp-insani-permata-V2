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
        padding: 2rem 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Header Section */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 1.5rem 2rem;
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .page-title {
        font-size: 1.875rem;
        font-weight: 800;
        background: linear-gradient(135deg, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Alert Styles */
    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #bbf7d0);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #166534;
        padding: 1rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    /* Form Container */
    .form-container {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 2rem 2.5rem;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2rem;
    }

    .form-container h2 {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1.125rem;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.8);
    }

    .form-control:focus {
        border-color: #22c55e;
        outline: none;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        background: rgba(255, 255, 255, 1);
    }

    /* Button Styles */
    .btn {
        padding: 0.875rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        box-shadow: 0 4px 16px rgba(34, 197, 94, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(34, 197, 94, 0.35);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #25845d, #1d6b4c);
        color: white;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.25);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.35);
    }

    .btn-cancel {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }

    .btn-cancel:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }

    /* Stats Section */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.98);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 8px 32px rgba(22, 163, 74, 0.08);
        border: 1px solid rgba(34, 197, 94, 0.1);
        text-align: center;
    }

    .stat-title {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Table Styles */
    .table-container {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(22, 163, 74, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.1);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        background: linear-gradient(135deg, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        padding: 1.5rem 2rem 1rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .modern-table th,
    .modern-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(34, 197, 94, 0.08);
        text-align: left;
    }

    .modern-table th {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        font-weight: 700;
        color: #166534;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .modern-table tbody tr {
        transition: all 0.2s ease;
    }

    .modern-table tbody tr:hover {
        background: rgba(34, 197, 94, 0.04);
    }

    .modern-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        gap: 0.25rem;
    }

    .badge-success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6b7280;
        font-style: italic;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .form-container {
            padding: 1.5rem;
        }

        .content-area {
            padding: 1rem;
        }

        .modern-table {
            font-size: 0.75rem;
        }

        .modern-table th,
        .modern-table td {
            padding: 0.75rem 0.5rem;
        }

        .stats-container {
            grid-template-columns: 1fr;
        }
    }

    /* Loading Animation */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Form Animation */
    .form-container {
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area promotion-page">
        <!-- Success Alert -->
        @if(session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-graduation-cap"></i>
                Kenaikan Kelas
            </h1>
            <a href="{{ route('siswa.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i>
                Data Siswa
            </a>
        </div>

        <!-- Form Section -->
        <div class="form-container">
            <h2>
                <i class="fas fa-level-up-alt"></i>
                Proses Kenaikan Kelas
            </h2>

            <form action="{{ route('kenaikan.proses') }}" method="POST" id="kenaikankelas-form" class="promotion-form">
                @csrf

                <div class="promotion-form-grid">
                    <div class="form-group">
                        <label for="sekolah_id" class="form-label">Pilih Sekolah</label>
                        <select name="sekolah_id" id="sekolah_id" class="form-control" required>
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach($semuaSekolah as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tahun_ajaran_id" class="form-label">Tahun Ajaran Baru</label>
                        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-control" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            @foreach($tahunAjaran as $tahun)
                                <option value="{{ $tahun->id }}">{{ $tahun->nama_tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="promotion-form-actions">
                    <button type="submit" class="btn btn-primary" data-confirm-title="Proses kenaikan kelas" data-confirm-message="Proses kenaikan kelas untuk siswa yang dipilih?" data-confirm-label="Proses kenaikan" data-confirm-tone="success">
                        <i class="fas fa-arrow-up"></i>
                        Proses Kenaikan Kelas
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        @if(session('promoted_students') && count(session('promoted_students')) > 0)
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-title">Total Siswa Naik Kelas</div>
                    <div class="stat-value">{{ count(session('promoted_students')) }}</div>
                </div>
            </div>

            <!-- Students Table -->
            <div class="table-container">
                <h3 class="section-title">
                    <i class="fas fa-arrow-circle-up"></i>
                    Daftar Siswa yang Naik Kelas
                </h3>
                
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas Sebelumnya</th>
                                <th>Kelas Baru</th>
                                <th>Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('promoted_students') as $student)
                                <tr>
                                    <td>{{ $student['nis'] }}</td>
                                    <td>
                                        <div style="font-weight: 600;">{{ $student['nama'] }}</div>
                                    </td>
                                    <td>
                                        <span style="color: #6b7280; font-size: 0.875rem;">
                                            Tingkat {{ $student['kelas_lama_tingkat'] }} {{ $student['kelas_lama_nama'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: #166534; font-weight: 600; font-size: 0.875rem;">
                                            Tingkat {{ $student['kelas_baru_tingkat'] }} {{ $student['kelas_baru_nama'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            <i class="fas fa-arrow-up"></i>
                                            Naik Kelas
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <form action="{{ route('kenaikan.cancel', ['siswa_id' => $student['id']]) }}" 
                                              method="POST" 
                                              style="display: inline;"
                                              data-confirm-title="Batalkan kenaikan kelas" data-confirm-message="Batalkan kenaikan kelas untuk {{ $student['nama'] }}?" data-confirm-label="Batalkan" data-confirm-tone="warning">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="btn btn-cancel">
                                                <i class="fas fa-times"></i>
                                                Batalkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(session('promoted_students'))
            <div class="table-container">
                <div class="empty-state">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <p>Tidak ada siswa yang naik kelas pada proses ini.</p>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('kenaikankelas-form');
    
    if (form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            }
        });
    }
});
</script>

@endsection
