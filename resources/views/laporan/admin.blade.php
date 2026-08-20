ak @extends('layouts.app')
@include('layouts.sidebar')

@section('content')
@push('page-styles')
<style>
    /* ====== STYLE NORMAL ====== */
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
    .table-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 2.5rem;
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
    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
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

    /* ====== STYLE KHUSUS CETAK ====== */
    @media print {
@page{
            size: landscape;
        }
        @page{
            size: landscape;
        }
        aside.sidebar,
        .page-header,
        .btn-primary,
        .btn-print,
        nav,
        header {
            display: none !important;
        }
        body, .main-content {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #fff !important;
        }
      
        .table-container {
            background: none;
            box-shadow: none;
            border: none;
            border-radius: 0;
        }
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .modern-table th,
        .modern-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .modern-table th {
            background: #f0f0f0;
            color: #000;
            font-weight: bold;
            text-transform: none;
            letter-spacing: normal;
        }
        h3.print-title {
            text-align: center;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        .kop-laporan {
            text-align: center;
            margin-bottom: 10px;
        }
        .kop-laporan h2 {
            margin: 0;
            font-size: 22px;
        }
        .kop-laporan p {
            margin: 2px 0;
            font-size: 14px;
        }
       
        .tanggal-laporan {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .print-footer {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            margin: 60px 0 10px 0;
            border-top: 1px solid #000;
        }
    }
</style>
@endpush

<div id="pi-report-page" class="main-content pi-report-page">
    @include('layouts.header')

    <div class="content-area pi-report-document">
        {{-- KOP LAPORAN KHUSUS CETAK --}}
        <div class="kop-laporan d-none d-print-block">
            <div style="display: flex; align-items: center; margin-bottom: 20px; text-align: center; justify-content: center;">
                <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo" style="width: 90px; height: 90px; margin-right: 20px; border-radius: 50%;">
                <div style="font-weight: bold; font-size: 16px; line-height: 1.3; text-align: left;">
                    YAYASAN KEMILAU PERMATA INSANI<br>
                    SD IT Permata Insani Islamic School<br>
                    <span style="font-weight: normal; font-size: 14px;">Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                </div>
            </div>
        </div>
        <hr class="print-hr d-none d-print-block">
        <div class="tanggal-laporan d-none d-print-block">
            Tanggal Laporan: {{ $tanggalLaporan }}
        </div>
        <h3 class="print-title d-none d-print-block">LAPORAN DATA ADMIN</h3>

        <div class="page-header no-print">
            <h2 class="page-title">
                <i class="fas fa-user-shield"></i> Laporan Admin
            </h2>
            <div>
                <button onclick="window.print()" class="btn-primary btn-print">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="{{ route('laporan.admin.excel') }}" class="btn-primary">Download Excel</a>
            </div>
        </div>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Admin</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $index => $admin)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $admin->nama_admin }}</td>
                            <td>{{ $admin->username }}</td>
                            <td>{{ $admin->role }}</td>
                            <td>
                                @if($admin->created_at)
                                    {{ \Carbon\Carbon::parse($admin->created_at)->translatedFormat('d-m-Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Footer untuk cetak -->
        <div class="print-footer d-none d-print-block">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Mengetahui,</p>
                    <p>Kepala Sekolah</p>
                    <div class="signature-line"></div>
                    <p class="signature-name">________________________</p>
                    <p class="signature-title">NIP. ________________________</p>
                </div>
                <div class="signature-box">
                    <p>Jambi, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                    <p>Bendahara</p>
                    <div class="signature-line"></div>
                    <p class="signature-name">________________________</p>
                    <p class="signature-title">NIP. ________________________</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
