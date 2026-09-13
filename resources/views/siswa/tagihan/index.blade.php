{{-- resources/views/siswa/tagihan/index.blade.php --}}
@extends('layouts.app')
@include('layouts.sidebar-siswa')

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

    .page-header {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        padding: 2rem;
        border-radius: 24px;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #166534, #14532d);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .table-container {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        overflow-x: auto;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
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

    .badge-lunas {
        background-color: #bbf7d0;
        color: #166534;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-belum {
        background-color: #fecaca;
        color: #b91c1c;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .academic-year-filter {
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .academic-year-filter label {
        margin: 0;
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
    }

    .academic-year-filter select {
        min-height: 40px;
        padding: 0 2.25rem 0 .8rem;
        color: #0f172a;
        background-color: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        font: inherit;
        font-size: .82rem;
        font-weight: 700;
    }

    .badge-mendatang {
        background-color: #fff7d6;
        color: #a15c07;
        border: 1px solid #f4c84a;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-nonaktif {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    @media (max-width: 640px) {
        .page-header {
            align-items: stretch;
            flex-direction: column;
            gap: 1rem;
        }

        .academic-year-filter {
            justify-content: space-between;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header-siswa')

    <div class="content-area">
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-file-invoice-dollar"></i> Tagihan Saya
            </h2>
            @if($academicYearData->isNotEmpty())
                <form class="academic-year-filter" method="GET" action="{{ route('siswa.tagihan.index') }}">
                    <label for="studentAcademicYear">Tahun ajaran</label>
                    <select id="studentAcademicYear" name="tahun_ajaran" onchange="this.form.submit()">
                        @foreach($academicYearData as $tahun)
                            <option value="{{ $tahun->id }}" {{ $selectedTahunAjaran?->id === $tahun->id ? 'selected' : '' }}>
                                {{ $tahun->label }}{{ $tahun->aktif ? ' · Aktif' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Jenis Tagihan</th>
                        <th>Tipe</th>
                        <th>Periode</th>
                        <th>Jatuh Tempo</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Sisa Cicilan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tagihanSaya as $index => $tagihan)
                        @php
                            $totalTerbayar = (float) $tagihan->pembayaran->sum('jumlah_bayar')
                                + (float) $tagihan->pembayaran->sum('diskon');
                            $sisaAktual = max(0, (float) $tagihan->nominal - $totalTerbayar);
                            $belumJatuhTempo = $sisaAktual > 0 && $tagihan->isBelumJatuhTempo();
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $tagihan->nama_tagihan_dinamis }}</td>
                            <td>{{ ucfirst($tagihan->tipe) }}</td>
                            <td>{{ $tagihan->periode ?? '-' }}</td>
                            <td>{{ $tagihan->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}</td>
                            <td>Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}</td>
                            <td>
                                @if(!$tagihan->is_active)
                                    <span class="badge-nonaktif">Tidak aktif</span>
                                @elseif($sisaAktual <= 0)
                                    <span class="badge-lunas">Lunas</span>
                                @elseif($belumJatuhTempo)
                                    <span class="badge-mendatang">Belum jatuh tempo</span>
                                @else
                                    <span class="badge-belum">Belum Lunas</span>
                                @endif
                            </td>
                            <td>
                                @if($tagihan->tipe === 'bulanan')
                                    {{ $tagihan->sisa_cicilan }} bulan
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-500">
                                Tidak ada tagihan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
