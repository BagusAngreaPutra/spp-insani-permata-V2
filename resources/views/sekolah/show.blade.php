@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="page-header">
            <div>
                <span class="page-eyebrow">Master data sekolah</span>
                <h1 class="page-title"><i class="fas fa-school"></i> Detail Sekolah</h1>
                <p class="page-subtitle">Identitas sekolah, jumlah siswa, dan susunan kelas dalam satu tampilan.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('sekolah.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('sekolah.edit', $sekolah) }}" class="btn btn-warning">
                    <i class="fas fa-pen"></i> Edit Sekolah
                </a>
            </div>
        </div>

        <section class="student-detail-section">
            <div class="student-detail-header">
                <i class="fas fa-building-columns" aria-hidden="true"></i>
                <div>
                    <h3>{{ $sekolah->nama_sekolah }}</h3>
                    <p>Kode sekolah {{ $sekolah->kode_sekolah ?: '-' }}</p>
                </div>
            </div>
            <div class="student-detail-grid">
                <div class="student-detail-item">
                    <span class="student-detail-label">Kode Sekolah</span>
                    <span class="student-detail-value">{{ $sekolah->kode_sekolah ?: '-' }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Jumlah Kelas</span>
                    <span class="student-detail-value">{{ $sekolah->kelas->count() }} kelas</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Jumlah Siswa</span>
                    <span class="student-detail-value">{{ $sekolah->siswa->count() }} siswa</span>
                </div>
                <div class="student-detail-item student-detail-wide">
                    <span class="student-detail-label">Alamat</span>
                    <span class="student-detail-value">{{ $sekolah->alamat ?: '-' }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Telepon</span>
                    <span class="student-detail-value">{{ $sekolah->telepon ?: '-' }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Email</span>
                    <span class="student-detail-value">{{ $sekolah->email ?: '-' }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Durasi Pendidikan</span>
                    <span class="student-detail-value">{{ $sekolah->durasi_pendidikan ? $sekolah->durasi_pendidikan.' tahun' : '-' }}</span>
                </div>
            </div>
        </section>

        <section class="student-detail-section" data-tone="purple">
            <div class="student-detail-header">
                <i class="fas fa-users-rectangle" aria-hidden="true"></i>
                <div>
                    <h3>Daftar Kelas</h3>
                    <p>Kelas yang terhubung dengan sekolah ini.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kelas</th>
                            <th>Tingkat</th>
                            <th>Tahun Ajaran</th>
                            <th>Jumlah Siswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sekolah->kelas->sortBy([['tingkat', 'asc'], ['nama_kelas', 'asc']]) as $kelas)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $kelas->kelas }}</strong></td>
                                <td>{{ $kelas->tingkat ? 'Tingkat '.$kelas->tingkat : 'Tanpa tingkat' }}</td>
                                <td>{{ $kelas->tahunAjaran->nama_tahun ?? '-' }}</td>
                                <td>{{ $kelas->siswa()->count() }} siswa</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada kelas pada sekolah ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
