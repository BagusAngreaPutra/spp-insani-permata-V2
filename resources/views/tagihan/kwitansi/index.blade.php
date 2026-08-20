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

    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .print-only {
        display: none;
    }

    @media print {
@page{
            size: landscape;
        }
        /* Sembunyikan elemen-elemen UI saat mencetak */
        .no-print, 
        .app-sidebar, 
        .sidebar-mobile-menu-btn, 
        .app-sidebar-header,
        .page-header,
        nav,
        header {
            display: none !important;
        }
        
        /* Tampilkan hanya konten kwitansi saat mencetak */
        body {
            margin: 0;
            padding: 0;
            background: white;
            position: static;
        }
        
        .main-content {
            margin: 0 !important;
            width: 100% !important;
            position: static !important;
            background: white !important;
            right: auto;
            top: auto;
        }
        
        .content-area {
            padding: 1rem !important;
        }
        
        .container {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
        
        .card-body {
            padding: 20px !important;
        }
        
        body {
            font-family: Arial, sans-serif !important;
        }
        
        /* Pastikan hanya konten kwitansi yang terlihat */
        .print-kwitansi {
            width: 100%;
            position: static;
        }
    }

    body {
        font-family: Arial, sans-serif;
    }
</style>
@endpush

<div class="main-content">
    <div class="content-area">
        <!-- Page Header dengan tombol cetak -->
        <div class="page-header no-print">
            <h1 class="page-title">
                <i class="fas fa-receipt"></i>
                Kwitansi Pembayaran
            </h1>
            <div class="no-print">
                <button onclick="printKwitansi()" class="btn-primary">
                    <i class="fas fa-print me-2"></i>Cetak Kwitansi
                </button>
            </div>
        </div>

        <div class="container print-kwitansi pi-print-document pi-receipt-document" id="kwitansi-container" data-receipt-document>
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card" style="border: none;">
                        <div class="card-body" style="padding: 40px;">
                            <!-- Header with logo and school info -->
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
                                        No. <span style="border-bottom: 1px solid black; display: inline-block; width: 180px; text-align: center; padding-bottom: 2px;">{{ $pembayaran->nomor_kwitansi ?? str_pad($pembayaran->id ?? 1, 6, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    @if(!empty($pembayaran->metode_bayar))
                                        @php
                                            $metodeBayarText = '';
                                            switch($pembayaran->metode_bayar) {
                                                case 'tunai':
                                                    $metodeBayarText = 'Tunai';
                                                    break;
                                                case 'transfer':
                                                    $metodeBayarText = 'Transfer';
                                                    break;
                                                case 'kjc':
                                                    $metodeBayarText = 'KJC';
                                                    break;
                                                case 'tabungan':
                                                    $metodeBayarText = 'Tabungan';
                                                    break;
                                                default:
                                                    $metodeBayarText = ucfirst($pembayaran->metode_bayar);
                                            }
                                        @endphp
                                        @if(!empty($metodeBayarText))
                                            <div style="font-size: 14px; margin-top: 8px;">
                                                ({{ $metodeBayarText }})
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Main content dengan border -->
                            <div style="border: 2px solid black; padding: 30px; margin-bottom: 40px;">
                                <!-- Sudah terima dari -->
                                <div style="display: flex; margin-bottom: 20px; align-items: center;">
                                    <span style="width: 140px; font-size: 16px;">Sudah terima dari</span>
                                    <span style="margin: 0 10px; font-size: 16px;">:</span>
                                    <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px;">{{ $pembayaran->siswa->nama ?? 'Nama Siswa Tidak Tersedia' }}</span>
                                </div>

                                <!-- Untuk Pembayaran -->
                                <div style="margin-bottom: 25px;">
                                    <div style="display: flex; margin-bottom: 15px; align-items: flex-start;">
                                        <span style="width: 140px; font-size: 16px;">Untuk Pembayaran</span>
                                        <span style="margin: 0 10px; font-size: 16px;">:</span>
                                        <div style="flex: 1;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                                <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px; margin-right: 20px;">
                                                    @if($pembayaran->tagihan && $pembayaran->tagihan->jenis_pembayaran_id === null)
                                                        SPP {{ $pembayaran->tagihan->periode ? ' - ' . \Carbon\Carbon::parse($pembayaran->tagihan->periode)->translatedFormat('F Y') : '' }}
                                                    @elseif($pembayaran->tagihan && $pembayaran->tagihan->jenisPembayaran)
                                                        {{ $pembayaran->tagihan->jenisPembayaran->nama_dengan_tahun ?? 'Pembayaran' }}
                                                        {{ $pembayaran->tagihan->periode ? ' - ' . \Carbon\Carbon::parse($pembayaran->tagihan->periode)->translatedFormat('F Y') : '' }}
                                                    @else
                                                        Pembayaran
                                                    @endif
                                                </span>
                                                <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($pembayaran->jumlah_bayar ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                            
                                            <!-- Diskon jika ada -->
                                            @if($pembayaran->diskon && $pembayaran->diskon > 0)
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                                    <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px; margin-right: 20px; margin-left: 20px;">Potongan</span>
                                                    <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($pembayaran->diskon, 0, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary totals -->
                                <div style="margin-left: 160px; margin-top: 25px;">
                                    @php
                                        $jumlah = $pembayaran->jumlah_bayar ?? 0;
                                        $potongan = $pembayaran->diskon ?? 0;
                                        $jumlahDiterima = $jumlah;
                                        $jumlah = $jumlah + $potongan;
                                    @endphp
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                        <span style="text-align: right; font-size: 16px; font-weight: bold;">Jumlah</span>
                                        <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
                                    </div>
                                    @if($potongan > 0)
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                        <span style="text-align: right; font-size: 16px; font-weight: bold;">Potongan</span>
                                        <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($potongan, 0, ',', '.') }}</span>
                                    </div>
                                    @endif
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                        <span style="text-align: right; font-size: 16px; font-weight: bold;">Jumlah Diterima</span>
                                        <span style="font-size: 16px; font-weight: bold;">Rp {{ number_format($jumlahDiterima, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <!-- Terbilang -->
                                <div style="margin-top: 30px;">
                                    <div style="display: flex; align-items: center;">
                                        <span style="width: 80px; font-size: 16px;">Terbilang</span>
                                        <span style="margin: 0 10px; font-size: 16px;">:</span>
                                        <span style="border-bottom: 2px solid black; flex: 1; min-height: 25px; padding: 2px 8px; font-size: 16px;">{{ ucwords(terbilang($jumlahDiterima)) }} Rupiah</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer signature -->
                            <div style="text-align: right; margin-top: 60px;">
                                @php
                                    \Carbon\Carbon::setLocale('id');
                                @endphp
                                <p style="margin-bottom: 80px; font-size: 16px;">Jambi, {{ $pembayaran->tanggal_bayar ? \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->translatedFormat('d F Y') : \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
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
// Fungsi untuk mencetak hanya bagian kwitansi
function printKwitansi() {
    // Simpan konten asli
    var originalContents = document.body.innerHTML;
    
    // Ambil hanya bagian kwitansi
    var printContents = document.getElementById('kwitansi-container').innerHTML;
    
    // Ganti body dengan hanya bagian kwitansi
    document.body.innerHTML = printContents;
    
    // Cetak
    window.print();
    
    // Kembalikan konten asli
    document.body.innerHTML = originalContents;
    
    // Reload halaman untuk memastikan semua fungsi kembali normal
    location.reload();
}

function terbilang(angka) {
    var bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    
    if (angka < 12) {
        return bilangan[angka];
    } else if (angka < 20) {
        return bilangan[angka - 10] + ' belas';
    } else if (angka < 100) {
        var puluhan = Math.floor(angka / 10);
        var satuan = angka % 10;
        return bilangan[puluhan] + ' puluh ' + bilangan[satuan];
    } else if (angka < 200) {
        return 'seratus ' + terbilang(angka - 100);
    } else if (angka < 1000) {
        var ratusan = Math.floor(angka / 100);
        return bilangan[ratusan] + ' ratus ' + terbilang(angka % 100);
    } else if (angka < 2000) {
        return 'seribu ' + terbilang(angka - 1000);
    } else if (angka < 1000000) {
        var ribuan = Math.floor(angka / 1000);
        return terbilang(ribuan) + ' ribu ' + terbilang(angka % 1000);
    } else if (angka < 1000000000) {
        var jutaan = Math.floor(angka / 1000000);
        return terbilang(jutaan) + ' juta ' + terbilang(angka % 1000000);
    }
    
    return String(angka);
}
</script>
@endsection
