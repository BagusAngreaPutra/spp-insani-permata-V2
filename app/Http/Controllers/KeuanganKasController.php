<?php

namespace App\Http\Controllers;

use App\Models\KoperasiPenjualan;
use App\Models\LogAktivitas;
use App\Models\Pemasukan;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KeuanganKasController extends Controller
{
    public function index(Request $request)
    {
        $sekolah = Sekolah::all();

        $data = $sekolah->map(function ($s) {
            $totalPembayaranSiswa = Pembayaran::whereHas('siswa', function ($q) use ($s) {
                $q->where('id_sekolah', $s->id);
            })->sum('jumlah_bayar');

            $totalPemasukanLain = Pemasukan::where('sekolah_id', $s->id)->sum('jumlah');
            $totalPenjualanKoperasi = KoperasiPenjualan::where('sekolah_id', $s->id)->sum('total');

            $totalPemasukan = $totalPembayaranSiswa + $totalPemasukanLain + $totalPenjualanKoperasi;
            $totalPengeluaran = Pengeluaran::where('sekolah_id', $s->id)->sum('jumlah');
            $saldo = $totalPemasukan - $totalPengeluaran;

            return [
                'sekolah' => $s,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo' => $saldo,
            ];
        });

        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::guard('web')->id(),
            'aktivitas' => 'Mengakses halaman laporan keuangan kas',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('keuangan_kas.index', compact('data'));
    }
}
