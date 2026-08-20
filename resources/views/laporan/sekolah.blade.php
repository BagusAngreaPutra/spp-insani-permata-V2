@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
    @media screen {
        #pi-report-page .school-group-cell {
            padding: 12px 14px !important;
            background: #f8fafc !important;
            border-color: #d0d5dd !important;
        }

        #pi-report-page .school-group-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        #pi-report-page .school-group-main {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 11px;
        }

        #pi-report-page .school-group-icon {
            display: inline-grid;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            place-items: center;
            color: #175cd3;
            background: #eff8ff;
            border: 1px solid #b2ddff;
            border-radius: 9px;
        }

        #pi-report-page .school-group-name {
            margin: 0;
            color: #101828;
            font-size: 13px;
            font-weight: 800;
        }

        #pi-report-page .school-group-meta {
            display: flex;
            margin-top: 3px;
            flex-wrap: wrap;
            gap: 4px 10px;
            color: #667085;
            font-size: 10.5px;
            line-height: 1.4;
        }

        #pi-report-page .school-group-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        #pi-report-page .school-group-stats {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 6px;
        }

        #pi-report-page .school-stat {
            display: inline-flex;
            min-height: 26px;
            padding: 4px 8px;
            align-items: center;
            gap: 5px;
            color: #475467;
            background: #fff;
            border: 1px solid #d0d5dd;
            border-radius: 7px;
            font-size: 10.5px;
            font-weight: 750;
            white-space: nowrap;
        }

        #pi-report-page .class-name {
            color: #101828;
            font-weight: 750;
        }

        #pi-report-page .class-empty {
            padding: 18px !important;
            color: #667085 !important;
            text-align: center !important;
            font-style: italic;
        }
    }

    @media screen and (max-width: 760px) {
        #pi-report-page .school-group-content {
            align-items: flex-start;
            flex-direction: column;
        }

        #pi-report-page .school-group-stats {
            padding-left: 45px;
            flex-wrap: wrap;
        }
    }

    @media print {
        #pi-report-page .school-group-cell {
            padding: 2mm !important;
            color: #000 !important;
            background: #fff !important;
            border: 1px solid #000 !important;
        }

        #pi-report-page .school-group-content {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 5mm !important;
        }

        #pi-report-page .school-group-main {
            display: block !important;
        }

        #pi-report-page .school-group-icon,
        #pi-report-page .school-group-meta i {
            display: none !important;
        }

        #pi-report-page .school-group-name {
            color: #000 !important;
            font-size: 9pt !important;
            font-weight: 700 !important;
        }

        #pi-report-page .school-group-meta,
        #pi-report-page .school-group-stats {
            display: flex !important;
            margin-top: 1mm !important;
            flex-wrap: wrap !important;
            gap: 2mm 4mm !important;
            color: #000 !important;
            font-size: 8pt !important;
        }

        #pi-report-page .school-stat {
            padding: 0 !important;
            color: #000 !important;
            background: transparent !important;
            border: 0 !important;
            font-size: 8pt !important;
        }
    }
</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page pi-school-class-report">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        <div class="kop-laporan d-none d-print-block">
            <div style="display:flex; align-items:center; justify-content:center;">
                <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo sekolah">
                <div>
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    SD IT Permata Insani Islamic School<br>
                    <span>Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                </div>
            </div>
        </div>
        <div class="tanggal-laporan d-none d-print-block">Tanggal laporan: {{ $tanggalLaporan }}</div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA SEKOLAH DAN KELAS</h3>

        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-school-flag"></i>
                Laporan sekolah & kelas
            </h1>
            <div class="header-actions">
                <button type="button" class="btn-primary print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <a href="{{ route('laporan.sekolah.excel', request()->query()) }}" class="btn-primary export-btn">
                    <i class="fas fa-file-excel"></i>
                    Download Excel
                </a>
            </div>
        </div>

        <section class="filter-section no-print">
            <h2 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter data
            </h2>

            <form method="GET" action="{{ route('laporan.sekolah') }}" class="report-filter-form pi-filter-form">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="sekolah_id"><i class="fas fa-school"></i> Sekolah</label>
                        <select name="sekolah_id" id="sekolah_id" class="form-control">
                            <option value="">Semua sekolah</option>
                            @foreach($daftarSekolah as $sekolahItem)
                                <option value="{{ $sekolahItem->id }}" {{ (string) request('sekolah_id') === (string) $sekolahItem->id ? 'selected' : '' }}>
                                    {{ $sekolahItem->nama_sekolah }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tahun_ajaran_id"><i class="fas fa-calendar-days"></i> Tahun ajaran</label>
                        <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-control">
                            <option value="">Semua tahun ajaran</option>
                            @foreach($daftarTahun as $tahunItem)
                                <option value="{{ $tahunItem->id }}" {{ (string) request('tahun_ajaran_id') === (string) $tahunItem->id ? 'selected' : '' }}>
                                    {{ $tahunItem->label }}{{ $tahunItem->aktif ? ' · Aktif' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tingkat"><i class="fas fa-layer-group"></i> Tingkat</label>
                        <select name="tingkat" id="tingkat" class="form-control">
                            <option value="">Semua tingkat</option>
                            @if($adaKelasTanpaTingkat)
                                <option value="none" {{ request('tingkat') === 'none' ? 'selected' : '' }}>Tanpa tingkat</option>
                            @endif
                            @foreach($daftarTingkat as $tingkatItem)
                                <option value="{{ $tingkatItem }}" {{ (string) request('tingkat') === (string) $tingkatItem ? 'selected' : '' }}>
                                    Tingkat {{ $tingkatItem }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="search"><i class="fas fa-search"></i> Cari sekolah atau kelas</label>
                        <input
                            type="search"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Nama sekolah, kode, kelas..."
                        >
                    </div>
                </div>

                <div class="filter-actions report-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Tampilkan
                    </button>
                    <a href="{{ route('laporan.sekolah') }}" class="btn btn-secondary">
                        <i class="fas fa-xmark"></i>
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="summary-section no-print" aria-label="Ringkasan laporan">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Sekolah ditampilkan</div>
                    <div class="summary-value">{{ number_format($ringkasan['sekolah'], 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Kelas ditampilkan</div>
                    <div class="summary-value">{{ number_format($ringkasan['kelas'], 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Siswa dalam kelas</div>
                    <div class="summary-value">{{ number_format($ringkasan['siswa'], 0, ',', '.') }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Sekolah tanpa kelas</div>
                    <div class="summary-value">{{ number_format($ringkasan['tanpa_kelas'], 0, ',', '.') }}</div>
                </div>
            </div>
        </section>

        <section class="table-container">
            <div class="table-header no-print">
                <h2 class="table-title">
                    <i class="fas fa-table-list"></i>
                    Daftar sekolah dan kelas
                </h2>
            </div>

            <div class="table-responsive">
                <table class="modern-table" data-sort-disabled="true" data-sortable="false">
                    <thead>
                        <tr>
                            <th style="width:7%;">No</th>
                            <th>Kelas</th>
                            <th style="width:15%;">Tingkat</th>
                            <th style="width:22%;">Tahun ajaran</th>
                            <th style="width:16%;">Jumlah siswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sekolahs as $sekolah)
                            @php
                                $contact = $sekolah->kontak ?: ($sekolah->telepon ?: '-');
                                $filteredStudents = $sekolah->kelas->sum('siswa_count');
                            @endphp
                            <tr class="school-group-row" data-sort-fixed>
                                <td colspan="5" class="school-group-cell">
                                    <div class="school-group-content">
                                        <div class="school-group-main">
                                            <span class="school-group-icon"><i class="fas fa-school"></i></span>
                                            <div>
                                                <p class="school-group-name">{{ $sekolah->nama_sekolah }}</p>
                                                <div class="school-group-meta">
                                                    <span><i class="fas fa-hashtag"></i> {{ $sekolah->kode_sekolah ?: 'Tanpa kode' }}</span>
                                                    <span><i class="fas fa-graduation-cap"></i> {{ $sekolah->jenjang ?: 'Jenjang belum diisi' }}</span>
                                                    <span><i class="fas fa-location-dot"></i> {{ $sekolah->alamat ?: 'Alamat belum diisi' }}</span>
                                                    <span><i class="fas fa-phone"></i> {{ $contact }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="school-group-stats">
                                            <span class="school-stat"><i class="fas fa-chalkboard"></i> {{ $sekolah->kelas->count() }} kelas</span>
                                            <span class="school-stat"><i class="fas fa-user-graduate"></i> {{ $filteredStudents }} siswa</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            @forelse($sekolah->kelas as $index => $kelas)
                                @php
                                    $className = trim((string) $kelas->nama_kelas);
                                    $className = in_array($className, ['', '-', '–'], true) ? 'Tanpa nama kelas' : $className;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="class-name">{{ $className }}</td>
                                    <td>{{ $kelas->label_tingkat }}</td>
                                    <td>{{ $kelas->tahunAjaran?->label ?? '-' }}</td>
                                    <td>{{ number_format($kelas->siswa_count, 0, ',', '.') }} siswa</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="class-empty">Belum ada kelas yang sesuai dengan filter untuk sekolah ini.</td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="5" class="class-empty">Tidak ada sekolah atau kelas yang sesuai dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="print-footer d-none d-print-block">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <div class="signature-line"></div>
                    <p>________________________</p>
                    <p>NIP. ________________________</p>
                </div>
                <div class="signature-box">
                    <p>Jambi, {{ $tanggalLaporan }}</p>
                    <p>Petugas administrasi</p>
                    <div class="signature-line"></div>
                    <p>________________________</p>
                    <p>NIP. ________________________</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
