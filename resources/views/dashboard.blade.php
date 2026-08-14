@extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@php
    $dashboardData = collect($data);
    $totalPemasukan = (float) $dashboardData->sum('pemasukan');
    $totalPengeluaran = (float) $dashboardData->sum('pengeluaran');
    $totalSaldo = $totalPemasukan - $totalPengeluaran;
    $filterAktif = request()->filled('tahun') || (request()->filled('start_date') && request()->filled('end_date'));

    if (request()->filled('tahun')) {
        $labelPeriode = 'Tahun ' . request('tahun');
    } elseif (request()->filled('start_date') && request()->filled('end_date')) {
        $labelPeriode = \Carbon\Carbon::parse(request('start_date'))->format('d M Y')
            . ' – '
            . \Carbon\Carbon::parse(request('end_date'))->format('d M Y');
    } else {
        $labelPeriode = 'Semua periode';
    }
@endphp

@push('page-styles')
<style>
    .overview-page {
        display: grid;
        gap: 18px;
    }

    .overview-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
    }

    .overview-eyebrow {
        margin: 0 0 3px;
        color: #98a2b3;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
    }

    .overview-title {
        margin: 0;
        color: #101828;
        font-size: clamp(25px, 2.6vw, 32px);
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1.16;
    }

    .overview-subtitle {
        margin: 7px 0 0;
        color: #667085;
        font-size: 12px;
    }

    .overview-heading-tools,
    .overview-actions,
    .overview-update {
        display: flex;
        align-items: center;
    }

    .overview-heading-tools {
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 13px;
    }

    .overview-update {
        gap: 7px;
        color: #667085;
        font-size: 11px;
        white-space: nowrap;
    }

    .overview-update i:first-child {
        color: #667085;
    }

    .overview-update .fa-circle-check {
        color: #12b76a;
    }

    .overview-actions {
        gap: 8px;
    }

    .overview-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        gap: 7px;
        min-height: 38px;
        padding: 8px 13px;
        color: #344054;
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 7px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .03);
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }

    .overview-action:hover {
        color: #101828;
        background: #f9fafb;
    }

    .overview-action.is-primary {
        color: #fff;
        background: #1d6b4c;
        border-color: #1d6b4c;
    }

    .overview-action.is-primary:hover {
        color: #fff;
        background: #15533b;
        border-color: #15533b;
    }

    .overview-filter-panel {
        display: none;
        padding: 16px;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
    }

    .overview-filter-panel.is-open {
        display: block;
    }

    .overview-filter-form {
        display: grid;
        grid-template-columns: repeat(3, minmax(150px, 1fr)) auto;
        align-items: end;
        gap: 12px;
    }

    .overview-field label {
        display: block;
        margin-bottom: 6px;
        color: #344054;
        font-size: 10px;
        font-weight: 600;
    }

    .overview-filter-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .overview-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .overview-stat {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .03);
    }

    .overview-stat-main {
        min-height: 112px;
        padding: 17px 18px 14px;
    }

    .overview-stat-icon {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        margin-bottom: 15px;
        color: #0eaa94;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .05);
        font-size: 13px;
    }

    .overview-stat[data-tone="expense"] .overview-stat-icon {
        color: #f04438;
    }

    .overview-stat[data-tone="balance"] .overview-stat-icon {
        color: #1d6b4c;
    }

    .overview-stat-label {
        margin: 0 0 3px;
        color: #667085;
        font-size: 11px;
        font-weight: 500;
    }

    .overview-stat-value {
        display: block;
        color: #101828;
        font-size: clamp(21px, 2.2vw, 27px);
        font-weight: 650;
        letter-spacing: -.04em;
        line-height: 1.2;
    }

    .overview-stat-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 37px;
        padding: 9px 18px;
        color: #667085;
        background: #fafbfc;
        border-top: 1px solid #e4e7ec;
        font-size: 10px;
    }

    .overview-stat-footer span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .overview-stat-footer i {
        color: #98a2b3;
    }

    .overview-panel {
        overflow: hidden;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .03);
    }

    .overview-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 56px;
        padding: 13px 18px;
        border-bottom: 1px solid #e4e7ec;
    }

    .overview-panel-title {
        margin: 0;
        color: #101828;
        font-size: 14px;
        font-weight: 650;
        letter-spacing: -.015em;
    }

    .overview-period {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 7px 10px;
        color: #475467;
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 7px;
        font-size: 10px;
        white-space: nowrap;
    }

    .overview-chart-meta {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 18px 0;
    }

    .overview-chart-total {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 8px;
    }

    .overview-chart-total strong {
        color: #101828;
        font-size: clamp(23px, 2.7vw, 32px);
        font-weight: 600;
        letter-spacing: -.04em;
    }

    .overview-chart-total span {
        color: #667085;
        font-size: 10px;
    }

    .overview-chart-legend {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        color: #667085;
        font-size: 10px;
    }

    .overview-chart-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .overview-chart-legend i {
        width: 7px;
        height: 7px;
        border-radius: 2px;
    }

    .overview-chart-legend .income {
        background: #15533b;
    }

    .overview-chart-legend .expense {
        background: #15b8a6;
    }

    .overview-chart {
        position: relative;
        height: 295px;
        padding: 8px 14px 14px;
    }

    .overview-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .overview-table {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
    }

    .overview-table th i {
        margin-right: 6px;
        color: #98a2b3;
        font-size: 9px;
    }

    .overview-school {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 230px;
    }

    .overview-school-icon {
        display: grid;
        flex: 0 0 31px;
        place-items: center;
        width: 31px;
        height: 31px;
        color: #1d6b4c;
        background: #f1f9f5;
        border-radius: 7px;
        font-size: 11px;
    }

    .overview-school strong,
    .overview-school small {
        display: block;
    }

    .overview-school strong {
        color: #101828;
        font-size: 11px;
        font-weight: 600;
    }

    .overview-school small {
        margin-top: 2px;
        color: #98a2b3;
        font-size: 9px;
    }

    .overview-number,
    .overview-money {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .overview-number {
        text-align: center;
    }

    .overview-money {
        text-align: right;
    }

    .overview-money.is-balance {
        color: #1d6b4c;
        font-weight: 600;
    }

    .overview-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-width: 112px;
        height: 36px;
        margin-left: auto;
        padding: 0 12px;
        color: #255ec7;
        background: #eef5ff;
        border: 1px solid #cbdcff;
        border-radius: 8px;
        box-shadow: 0 3px 8px rgba(37, 94, 199, .08);
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
    }

    .overview-row-action:hover {
        color: #fff;
        background: #3478ed;
        border-color: #2867d3;
        box-shadow: 0 5px 12px rgba(40, 103, 211, .18);
        transform: translateY(-1px);
    }

    .overview-row-action i {
        font-size: 11px;
    }

    .overview-action-column,
    .overview-action-cell {
        width: 136px;
        text-align: right;
    }

    .overview-table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 43px;
        padding: 10px 18px;
        color: #667085;
        background: #fafbfc;
        border-top: 1px solid #e4e7ec;
        font-size: 10px;
    }

    .overview-empty {
        display: grid;
        place-items: center;
        min-height: 220px;
        padding: 30px;
        color: #667085;
        text-align: center;
    }

    .overview-empty i {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        margin: 0 auto 10px;
        color: #98a2b3;
        background: #f2f4f7;
        border-radius: 9px;
    }

    .overview-empty strong,
    .overview-empty span {
        display: block;
    }

    .overview-empty strong {
        color: #344054;
        font-size: 13px;
    }

    .overview-empty span {
        margin-top: 4px;
        font-size: 10px;
    }

    @media (max-width: 980px) {
        .overview-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .overview-heading-tools {
            width: 100%;
            justify-content: space-between;
        }

        .overview-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .overview-page {
            gap: 14px;
        }

        .overview-heading-tools,
        .overview-update,
        .overview-actions {
            width: 100%;
        }

        .overview-heading-tools {
            align-items: flex-start;
            flex-direction: column;
        }

        .overview-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .overview-action {
            overflow: hidden;
            width: 100%;
            text-overflow: ellipsis;
        }

        .overview-filter-form,
        .overview-stats {
            grid-template-columns: 1fr;
        }

        .overview-filter-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .overview-filter-buttons > * {
            width: 100%;
        }

        .overview-chart-meta {
            align-items: flex-start;
            flex-direction: column;
        }

        .overview-chart {
            height: 260px;
            padding-inline: 8px;
        }

        .overview-panel-header,
        .overview-table-footer {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush

<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="overview-page">
            <header class="overview-heading">
                <div>
                    <p class="overview-eyebrow">Insight</p>
                    <h1 class="overview-title" data-page-title>Ringkasan</h1>
                    <p class="overview-subtitle">Pantau kondisi keuangan dan data sekolah dalam satu tampilan.</p>
                </div>

                <div class="overview-heading-tools">
                    <div class="overview-update">
                        <i class="far fa-calendar-check" aria-hidden="true"></i>
                        <span>Diperbarui {{ now()->translatedFormat('d F Y') }}</span>
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                    </div>

                    <div class="overview-actions">
                        <button class="overview-action" id="overviewFilterToggle" type="button" aria-expanded="{{ $filterAktif ? 'true' : 'false' }}" aria-controls="overviewFilterPanel">
                            <i class="fas fa-sliders" aria-hidden="true"></i>
                            <span>Filter</span>
                        </button>
                        <a class="overview-action is-primary" href="{{ route('laporan.pembayaran') }}">
                            <i class="fas fa-chart-column" aria-hidden="true"></i>
                            <span>Lihat laporan</span>
                        </a>
                    </div>
                </div>
            </header>

            <section class="overview-filter-panel {{ $filterAktif ? 'is-open' : '' }}" id="overviewFilterPanel" aria-label="Filter ringkasan">
                <form class="overview-filter-form" method="GET" action="{{ route('dashboard') }}">
                    <div class="overview-field">
                        <label for="overviewYear">Tahun</label>
                        <input id="overviewYear" type="number" name="tahun" placeholder="{{ now()->year }}" value="{{ request('tahun') }}" min="2000" max="2100">
                    </div>
                    <div class="overview-field">
                        <label for="overviewStart">Tanggal mulai</label>
                        <input id="overviewStart" type="date" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="overview-field">
                        <label for="overviewEnd">Tanggal akhir</label>
                        <input id="overviewEnd" type="date" name="end_date" value="{{ request('end_date') }}">
                    </div>
                    <div class="overview-filter-buttons">
                        <button class="overview-action is-primary" type="submit">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <span>Terapkan</span>
                        </button>
                        <a class="overview-action" href="{{ route('dashboard') }}">
                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </form>
            </section>

            <section class="overview-stats" aria-label="Statistik keuangan">
                <article class="overview-stat" data-tone="income">
                    <div class="overview-stat-main">
                        <span class="overview-stat-icon"><i class="fas fa-coins" aria-hidden="true"></i></span>
                        <p class="overview-stat-label">Total pemasukan</p>
                        <strong class="overview-stat-value">Rp{{ number_format($totalPemasukan, 0, ',', '.') }}</strong>
                    </div>
                    <footer class="overview-stat-footer">
                        <span><i class="far fa-calendar" aria-hidden="true"></i>{{ $labelPeriode }}</span>
                    </footer>
                </article>

                <article class="overview-stat" data-tone="expense">
                    <div class="overview-stat-main">
                        <span class="overview-stat-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                        <p class="overview-stat-label">Total pengeluaran</p>
                        <strong class="overview-stat-value">Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}</strong>
                    </div>
                    <footer class="overview-stat-footer">
                        <span><i class="far fa-calendar" aria-hidden="true"></i>{{ $labelPeriode }}</span>
                    </footer>
                </article>

                <article class="overview-stat" data-tone="balance">
                    <div class="overview-stat-main">
                        <span class="overview-stat-icon"><i class="fas fa-wallet" aria-hidden="true"></i></span>
                        <p class="overview-stat-label">Saldo kas</p>
                        <strong class="overview-stat-value">Rp{{ number_format($totalSaldo, 0, ',', '.') }}</strong>
                    </div>
                    <footer class="overview-stat-footer">
                        <span><i class="fas fa-building-columns" aria-hidden="true"></i>{{ $dashboardData->count() }} sekolah</span>
                    </footer>
                </article>
            </section>

            <section class="overview-panel" aria-labelledby="overviewChartTitle">
                <header class="overview-panel-header">
                    <h2 class="overview-panel-title" id="overviewChartTitle">Arus keuangan</h2>
                    <span class="overview-period">
                        <i class="far fa-calendar" aria-hidden="true"></i>
                        {{ $labelPeriode }}
                    </span>
                </header>

                @if($dashboardData->isNotEmpty())
                    <div class="overview-chart-meta">
                        <div class="overview-chart-total">
                            <strong>Rp{{ number_format($totalPemasukan, 0, ',', '.') }}</strong>
                            <span>total pemasukan</span>
                        </div>
                        <div class="overview-chart-legend" aria-hidden="true">
                            <span><i class="income"></i>Pemasukan</span>
                            <span><i class="expense"></i>Pengeluaran</span>
                        </div>
                    </div>
                    <div class="overview-chart">
                        <canvas id="overviewFinancialChart"></canvas>
                    </div>
                @else
                    <div class="overview-empty">
                        <div>
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                            <strong>Belum ada data keuangan</strong>
                            <span>Data akan tampil setelah transaksi tersedia.</span>
                        </div>
                    </div>
                @endif
            </section>

            <section class="overview-panel" aria-labelledby="overviewSchoolTitle">
                <header class="overview-panel-header">
                    <h2 class="overview-panel-title" id="overviewSchoolTitle">Ringkasan sekolah</h2>
                    <a class="overview-action" href="{{ route('sekolah.index') }}">
                        <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        <span>Kelola sekolah</span>
                    </a>
                </header>

                @if($dashboardData->isNotEmpty())
                    <div class="overview-table-wrap">
                        <table class="overview-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-school" aria-hidden="true"></i>Sekolah</th>
                                    <th class="overview-number"><i class="fas fa-users" aria-hidden="true"></i>Siswa</th>
                                    <th class="overview-number"><i class="fas fa-door-open" aria-hidden="true"></i>Kelas</th>
                                    <th class="overview-money"><i class="fas fa-arrow-trend-up" aria-hidden="true"></i>Pemasukan</th>
                                    <th class="overview-money"><i class="fas fa-arrow-trend-down" aria-hidden="true"></i>Pengeluaran</th>
                                    <th class="overview-money"><i class="fas fa-wallet" aria-hidden="true"></i>Saldo</th>
                                    <th class="overview-action-column"><i class="fas fa-chart-pie" aria-hidden="true"></i>Ringkasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dashboardData as $item)
                                    <tr>
                                        <td>
                                            <div class="overview-school">
                                                <span class="overview-school-icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span>
                                                <span>
                                                    <strong>{{ $item['nama'] }}</strong>
                                                    <small>{{ $item['jenjang'] ?: 'Sekolah' }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="overview-number">{{ number_format($item['jumlah_siswa']) }}</td>
                                        <td class="overview-number">{{ number_format($item['jumlah_kelas']) }}</td>
                                        <td class="overview-money">Rp{{ number_format($item['pemasukan'], 0, ',', '.') }}</td>
                                        <td class="overview-money">Rp{{ number_format($item['pengeluaran'], 0, ',', '.') }}</td>
                                        <td class="overview-money is-balance">Rp{{ number_format($item['saldo_kas'], 0, ',', '.') }}</td>
                                        <td class="overview-action-cell">
                                            <a class="overview-row-action" href="{{ route('sekolah.show', $item['id']) }}" title="Lihat {{ $item['nama'] }}" aria-label="Lihat {{ $item['nama'] }}">
                                                <i class="fas fa-chart-pie" aria-hidden="true"></i>
                                                <span>Ringkasan</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <footer class="overview-table-footer">
                        <span>{{ $dashboardData->count() }} sekolah ditampilkan</span>
                        <span>Total saldo Rp{{ number_format($totalSaldo, 0, ',', '.') }}</span>
                    </footer>
                @else
                    <div class="overview-empty">
                        <div>
                            <i class="fas fa-school" aria-hidden="true"></i>
                            <strong>Belum ada sekolah</strong>
                            <span>Tambahkan sekolah untuk mulai melihat ringkasan.</span>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterToggle = document.getElementById('overviewFilterToggle');
    const filterPanel = document.getElementById('overviewFilterPanel');

    filterToggle?.addEventListener('click', function () {
        const isOpen = filterPanel.classList.toggle('is-open');
        filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    const chartCanvas = document.getElementById('overviewFinancialChart');
    if (!chartCanvas || typeof Chart === 'undefined') return;

    const schoolData = @json($dashboardData->values());

    new Chart(chartCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: schoolData.map(item => item.nama),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: schoolData.map(item => item.pemasukan),
                    borderColor: '#15533b',
                    backgroundColor: 'rgba(37, 132, 93, .10)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#15533b',
                    fill: true,
                    tension: .34
                },
                {
                    label: 'Pengeluaran',
                    data: schoolData.map(item => item.pengeluaran),
                    borderColor: '#15b8a6',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [6, 5],
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#15b8a6',
                    fill: false,
                    tension: .34
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    borderColor: '#e4e7ec',
                    borderWidth: 1,
                    titleColor: '#101828',
                    bodyColor: '#475467',
                    padding: 11,
                    displayColors: true,
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': Rp' + Number(context.parsed.y).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        color: '#98a2b3',
                        maxRotation: 0,
                        callback: function (value) {
                            const label = this.getLabelForValue(value);
                            return label.length > 18 ? label.substring(0, 18) + '…' : label;
                        },
                        font: {
                            size: 9
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f2f5',
                        drawTicks: false
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        color: '#98a2b3',
                        padding: 8,
                        callback: function (value) {
                            if (value >= 1000000000) return 'Rp' + (value / 1000000000).toFixed(1) + ' M';
                            if (value >= 1000000) return 'Rp' + (value / 1000000).toFixed(0) + ' jt';
                            if (value >= 1000) return 'Rp' + (value / 1000).toFixed(0) + ' rb';
                            return 'Rp' + value;
                        },
                        font: {
                            size: 9
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
