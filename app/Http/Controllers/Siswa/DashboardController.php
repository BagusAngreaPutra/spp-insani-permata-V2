<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use App\Models\TahunAjaran;


class DashboardController extends Controller
{
    public function index()
    {
        $siswa = Auth::guard('siswa')->user();
        $activeAcademicYearId = TahunAjaran::query()->where('aktif', true)->value('id')
            ?? $siswa->tahun_ajaran_id;

        // Ringkasan utama hanya memakai tahun ajaran aktif. Arsip tahun lama
        // tetap tersedia dari filter pada halaman Tagihan Saya.
        $activeBills = Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->when($activeAcademicYearId, fn ($query) => $query->where('tahun_ajaran_id', $activeAcademicYearId));
        $totalTagihan = (clone $activeBills)->sum('nominal');

        $totalPembayaran = Pembayaran::where('siswa_id', $siswa->id)
            ->when(
                $activeAcademicYearId,
                fn ($query) => $query->whereHas('tagihan', fn ($tagihanQuery) =>
                    $tagihanQuery->where('tahun_ajaran_id', $activeAcademicYearId)
                )
            )
            ->sum('jumlah_bayar');

        // Tagihan yang statusnya belum lunas
        $tagihanAktif = (clone $activeBills)
                            ->where('status', 'belum')
                            ->get();

        // Riwayat pembayaran terakhir (5 terbaru)
        $riwayatPembayaran = Pembayaran::where('siswa_id', $siswa->id)
                                ->latest()
                                ->take(5)
                                ->get();

        return view('siswa.dashboard', [
            'total_tagihan'      => $totalTagihan,
            'total_pembayaran'   => $totalPembayaran,
            'tagihan_aktif'      => $tagihanAktif,
            'riwayat_pembayaran' => $riwayatPembayaran,
        ]);
    }
}
