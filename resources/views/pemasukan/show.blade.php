@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="page-header">
            <div>
                <span class="page-eyebrow">Keuangan sekolah</span>
                <h1 class="page-title"><i class="fas fa-circle-info"></i> Detail Pemasukan</h1>
                <p class="page-subtitle">Rincian sumber dan nilai dana masuk yang tercatat.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('pemasukan.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('pemasukan.edit', $pemasukan) }}" class="btn btn-warning">
                    <i class="fas fa-pen"></i> Edit Pemasukan
                </a>
            </div>
        </div>

        <section class="student-detail-section" data-tone="green">
            <div class="student-detail-header">
                <i class="fas fa-arrow-trend-up" aria-hidden="true"></i>
                <div>
                    <h3>{{ $pemasukan->sumber ?: 'Pemasukan sekolah' }}</h3>
                    <p>Dicatat untuk {{ $pemasukan->sekolah->nama_sekolah ?? '-' }}.</p>
                </div>
            </div>
            <div class="student-detail-grid">
                <div class="student-detail-item">
                    <span class="student-detail-label">Tanggal</span>
                    <span class="student-detail-value">{{ \Carbon\Carbon::parse($pemasukan->tanggal)->translatedFormat('d F Y') }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Nominal</span>
                    <span class="student-detail-value">Rp{{ number_format($pemasukan->jumlah, 0, ',', '.') }}</span>
                </div>
                <div class="student-detail-item">
                    <span class="student-detail-label">Sekolah</span>
                    <span class="student-detail-value">{{ $pemasukan->sekolah->nama_sekolah ?? '-' }}</span>
                </div>
                <div class="student-detail-item student-detail-wide">
                    <span class="student-detail-label">Keterangan</span>
                    <span class="student-detail-value">{{ $pemasukan->keterangan ?: '-' }}</span>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
