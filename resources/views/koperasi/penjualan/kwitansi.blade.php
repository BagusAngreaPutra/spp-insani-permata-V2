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

    .content-area { padding: 2rem 1.5rem; }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(34, 197, 94, 0.1);
        border: 2px solid rgba(34, 197, 94, 0.1);
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #14532d, #166534, #22c55e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .btn-primary, .btn-secondary {
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .btn-secondary { background: linear-gradient(135deg, #64748b, #475569); }

    @media print {
        @page { size: landscape; }

        .no-print,
        .app-sidebar,
        .sidebar-mobile-menu-btn,
        .app-sidebar-header,
        .page-header,
        nav,
        header {
            display: none !important;
        }

        body {
            margin: 0;
            padding: 0;
            background: white;
            position: static;
            font-family: Arial, sans-serif !important;
        }

        .main-content {
            margin: 0 !important;
            width: 100% !important;
            position: static !important;
            background: white !important;
            right: auto;
            top: auto;
        }

        .content-area { padding: 1rem !important; }
        .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; margin: 0 !important; }
        .card-body { padding: 20px !important; }
        .print-kwitansi { width: 100%; position: static; }
    }

    body { font-family: Arial, sans-serif; }
</style>
@endpush

@php
    \Carbon\Carbon::setLocale('id');
    $tanggal = $penjualan->tanggal
        ? \Carbon\Carbon::parse($penjualan->tanggal)->translatedFormat('d F Y')
        : \Carbon\Carbon::now()->translatedFormat('d F Y');
@endphp

<div class="main-content">
    <div class="content-area">
        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-receipt"></i>
                Kwitansi Penjualan Koperasi
            </h1>
            <div class="no-print" style="display:flex; gap:0.75rem;">
                <a href="{{ route('koperasi.penjualan.show', $penjualan->id) }}" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button onclick="printKwitansi()" class="btn-primary">
                    <i class="fas fa-print"></i> Cetak Kwitansi
                </button>
            </div>
        </div>

        <div class="container print-kwitansi pi-print-document pi-receipt-document" id="kwitansi-container" data-receipt-document>
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card" style="border: none;">
                        <div class="card-body" style="padding: 40px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
                                <div style="display: flex; align-items: center;">
                                    <img src="{{ asset('images/logo.jpg') }}" onerror="this.style.display='none'" alt="Logo" style="width: 90px; height: 90px; margin-right: 20px; border-radius: 50%;">
                                    <div style="font-weight: bold; font-size: 16px; line-height: 1.3;">
                                        YAYASAN KEMILAU PERMATA INSANI<br>
                                        PAUD (KB/TK) - Permata Insani Islamic School<br>
                                        <span style="font-weight: normal; font-size: 14px;">Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi</span>
                                    </div>
                                </div>
                                <div style="text-align: center; min-width: 140px;">
                                    <div style="font-weight: bold; font-size: 32px; text-decoration: underline; margin-bottom: 15px;">
                                        KWITANSI
                                    </div>
                                    <div style="font-size: 16px;">
                                        No. <span style="border-bottom: 1px solid black; display: inline-block; width: 180px; text-align: center; padding-bottom: 2px;">{{ $penjualan->kode_transaksi }}</span>
                                    </div>
                                    <div style="font-size: 14px; margin-top: 8px;">
                                        (Koperasi)
                                    </div>
                                </div>
                            </div>

                            <div style="border: 2px solid black; padding: 30px; margin-bottom: 40px;">
                                <div style="display: flex; margin-bottom: 20px; align-items: center;">
                                    <span style="width: 140px; font-size: 16px;">Sudah terima dari</span>
                                    <span style="margin: 0 10px; font-size: 16px;">:</span>
                                    <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px;">
                                        {{ $penjualan->siswa->nama ?? 'Nama Siswa Tidak Tersedia' }}
                                        @if($penjualan->siswa)
                                            - {{ $penjualan->siswa->nis }} ({{ $penjualan->siswa->kelas->kelas ?? '-' }})
                                        @endif
                                    </span>
                                </div>

                                <div style="margin-bottom: 25px;">
                                    <div style="display: flex; margin-bottom: 15px; align-items: flex-start;">
                                        <span style="width: 140px; font-size: 16px;">Untuk Pembayaran</span>
                                        <span style="margin: 0 10px; font-size: 16px;">:</span>
                                        <div style="flex: 1;">
                                            @foreach($penjualan->details as $detail)
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                                    <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px; margin-right: 20px;">
                                                        {{ $loop->iteration }}. {{ $detail->nama_barang }} ({{ $detail->jumlah }} x Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }})
                                                    </span>
                                                    <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-left: 160px; margin-top: 25px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                        <span style="text-align: right; font-size: 16px; font-weight: bold;">Jumlah Diterima</span>
                                        <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <div style="margin-top: 30px;">
                                    <div style="display: flex; align-items: center;">
                                        <span style="width: 80px; font-size: 16px;">Terbilang</span>
                                        <span style="margin: 0 10px; font-size: 16px;">:</span>
                                        <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px;">{{ ucwords(terbilang((int) $penjualan->total)) }} Rupiah</span>
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right; margin-top: 60px;">
                                <p style="margin-bottom: 80px; font-size: 16px;">Jambi, {{ $tanggal }}</p>
                                <div style="border-bottom: 2px solid black; width: 200px; margin-left: auto;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printKwitansi() {
    var originalContents = document.body.innerHTML;
    var printContents = document.getElementById('kwitansi-container').innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}
</script>
@endsection
