<?php

namespace App\Http\Controllers;

use App\Models\JenisPembayaran;
use App\Models\Kelas;
use App\Models\KoperasiPenjualan;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::id(),
            'aktivitas' => 'Mengakses halaman dashboard',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $tahun = $request->input('tahun');
        [$rangeStart, $rangeEnd] = $this->resolveDateRange($tahun, $startDate, $endDate);

        // Count siswa dan kelas dijalankan sebagai subquery tunggal, bukan dua
        // query tambahan untuk setiap sekolah.
        $sekolahList = Sekolah::query()
            ->withCount(['siswa', 'kelas'])
            ->orderBy('nama_sekolah')
            ->get();

        $setupStats = [
            'sekolah' => $sekolahList->count(),
            'tahun_ajaran' => TahunAjaran::count(),
            'kelas' => Kelas::count(),
            'siswa' => Siswa::count(),
            'jenis_pembayaran' => JenisPembayaran::count(),
            'tagihan' => Tagihan::count(),
            'pembayaran' => Pembayaran::count(),
        ];

        // Setiap sumber keuangan diringkas satu kali untuk semua sekolah. Ini
        // menjaga jumlah query tetap konstan ketika jumlah sekolah bertambah.
        $pembayaranPerSekolah = Pembayaran::query()
            ->join('siswa', 'siswa.id', '=', 'pembayaran.siswa_id')
            ->when($rangeStart && $rangeEnd, function ($query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('pembayaran.tanggal_bayar', [$rangeStart, $rangeEnd]);
            })
            ->selectRaw('siswa.id_sekolah AS sekolah_id, COALESCE(SUM(pembayaran.jumlah_bayar), 0) AS total')
            ->groupBy('siswa.id_sekolah')
            ->pluck('total', 'sekolah_id');

        $pemasukanPerSekolah = Pemasukan::query()
            ->when($rangeStart && $rangeEnd, function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('tanggal', [$rangeStart, $rangeEnd]);
            })
            ->selectRaw('sekolah_id, COALESCE(SUM(jumlah), 0) AS total')
            ->groupBy('sekolah_id')
            ->pluck('total', 'sekolah_id');

        $pengeluaranPerSekolah = Pengeluaran::query()
            ->when($rangeStart && $rangeEnd, function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('tanggal', [$rangeStart, $rangeEnd]);
            })
            ->selectRaw('sekolah_id, COALESCE(SUM(jumlah), 0) AS total')
            ->groupBy('sekolah_id')
            ->pluck('total', 'sekolah_id');

        $koperasiPerSekolah = collect();
        if (Schema::hasTable('koperasi_penjualan')) {
            $koperasiPerSekolah = KoperasiPenjualan::query()
                ->when($rangeStart && $rangeEnd, function (Builder $query) use ($rangeStart, $rangeEnd): void {
                    $query->whereBetween('tanggal', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
                })
                ->selectRaw('sekolah_id, COALESCE(SUM(total), 0) AS total')
                ->groupBy('sekolah_id')
                ->pluck('total', 'sekolah_id');
        }

        $data = $sekolahList->map(function (Sekolah $sekolah) use (
            $pembayaranPerSekolah,
            $pemasukanPerSekolah,
            $pengeluaranPerSekolah,
            $koperasiPerSekolah
        ): array {
            $totalPembayaran = (float) ($pembayaranPerSekolah[$sekolah->id] ?? 0);
            $totalPemasukanManual = (float) ($pemasukanPerSekolah[$sekolah->id] ?? 0);
            $totalKoperasi = (float) ($koperasiPerSekolah[$sekolah->id] ?? 0);
            $totalPengeluaran = (float) ($pengeluaranPerSekolah[$sekolah->id] ?? 0);
            $totalPemasukan = $totalPembayaran + $totalPemasukanManual + $totalKoperasi;

            return [
                'id' => $sekolah->id,
                'nama' => $sekolah->nama_sekolah,
                'jenjang' => strtoupper((string) ($sekolah->jenjang ?? '')),
                'jumlah_siswa' => $sekolah->siswa_count,
                'jumlah_kelas' => $sekolah->kelas_count,
                'pemasukan' => $totalPemasukan,
                'pemasukan_koperasi' => $totalKoperasi,
                'pengeluaran' => $totalPengeluaran,
                'saldo_kas' => $totalPemasukan - $totalPengeluaran,
            ];
        })->all();

        return view('dashboard', compact('data', 'setupStats', 'startDate', 'endDate', 'tahun'));
    }

    /**
     * Mengubah filter tahun/rentang tanggal menjadi batas waktu yang dapat
     * memakai index database. whereYear() sengaja dihindari karena membungkus
     * kolom dengan fungsi SQL dan membuat index tanggal tidak efektif.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveDateRange($tahun, $startDate, $endDate): array
    {
        if ($tahun && preg_match('/^(20\d{2}|2100)$/', (string) $tahun)) {
            return [
                Carbon::create((int) $tahun, 1, 1)->startOfDay(),
                Carbon::create((int) $tahun, 12, 31)->endOfDay(),
            ];
        }

        if ($startDate && $endDate) {
            try {
                return [
                    Carbon::createFromFormat('Y-m-d', (string) $startDate)->startOfDay(),
                    Carbon::createFromFormat('Y-m-d', (string) $endDate)->endOfDay(),
                ];
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return [null, null];
    }
}
