<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Auth;
use App\Services\TagihanPeriodReconciler;
use App\Models\LogAktivitas; // ✅ tambahkan import

class TagihanSiswaController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Ambil data siswa yang sedang login
        $siswa = Auth::guard('siswa')->user();

        // ✅ Catat log aktivitas siswa
        LogAktivitas::create([
            'aktor_type' => 'siswa',
            'aktor_id'   => $siswa->id,
            'aktivitas'  => 'Membuka halaman daftar Tagihan Siswa',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // ✅ Ambil tagihan hanya milik siswa ini
        $periodReconciler = app(TagihanPeriodReconciler::class);
        $academicYearData = TahunAjaran::validPeriods();
        $selectedTahunAjaran = $request->filled('tahun_ajaran')
            ? $academicYearData->firstWhere('id', $request->integer('tahun_ajaran'))
            : ($academicYearData->firstWhere('aktif', true)
                ?? $academicYearData->firstWhere('id', $siswa->tahun_ajaran_id)
                ?? $academicYearData->first());
        $tagihanSaya = Tagihan::with(['jenisPembayaran.tahunAjaran', 'pembayaran'])
                        ->where('siswa_id', $siswa->id)
                        ->when(
                            $selectedTahunAjaran,
                            fn ($query) => $query->where('tahun_ajaran_id', $selectedTahunAjaran->id)
                        )
                        ->orderBy('tanggal_jatuh_tempo', 'asc')
                        ->get()
                        ->filter(fn (Tagihan $tagihan) =>
                            $periodReconciler->belongsToConfiguredPeriod($tagihan)
                        );

        // ✅ Kirim ke view
        return view('siswa.tagihan.index', [
            'siswa' => $siswa,
            'tagihanSaya' => $tagihanSaya,
            'academicYearData' => $academicYearData,
            'selectedTahunAjaran' => $selectedTahunAjaran,
        ]);
    }
}
