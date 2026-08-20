<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RiwayatKelulusan;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Sekolah;
use App\Models\Kelas;


class LaporanKelulusanController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjaranId  = $request->get('tahun_ajaran_id');
        $sekolahId      = $request->get('sekolah_id');
        $kelasId        = $request->get('kelas_id');
        $tanggalLulus   = $request->get('tanggal_lulus');
        $search         = $request->get('search');

        if ($sekolahId && $kelasId
            && !Kelas::whereKey($kelasId)->where('sekolah_id', $sekolahId)->exists()) {
            $kelasId = null;
            $request->merge(['kelas_id' => null]);
        }

        // 🔗 Ambil kelulusan lengkap dengan relasi
        $kelulusanQuery = RiwayatKelulusan::with(['siswa', 'sekolah', 'kelas', 'tahunAjaran'])
            ->orderBy('tanggal_lulus', 'desc');

        // ✅ Filter berdasarkan tahun ajaran
        if ($tahunAjaranId) {
            $kelulusanQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        // ✅ Filter berdasarkan sekolah
        if ($sekolahId) {
            $kelulusanQuery->where('sekolah_id', $sekolahId);
        }

        // ✅ Filter berdasarkan kelas
        if ($kelasId) {
            $kelulusanQuery->where('kelas_id', $kelasId);
        }

        // ✅ Filter berdasarkan tanggal lulus
        if ($tanggalLulus) {
            $kelulusanQuery->whereDate('tanggal_lulus', $tanggalLulus);
        }

        // ✅ Filter berdasarkan nama siswa
        if ($search) {
            $kelulusanQuery->whereHas('siswa', function ($query) use ($search) {
                $query->where('nama', 'like', '%' . $search . '%');
            });
        }

        $kelulusan = $kelulusanQuery->get();

        // 🔄 Ambil info sekolah dari data pertama atau default
        $sekolah = null;
        if ($sekolahId) {
            $sekolah = Sekolah::find($sekolahId);
        } elseif ($kelulusan->isNotEmpty() && $kelulusan->first()->sekolah) {
            $sekolah = $kelulusan->first()->sekolah;
        } 
        
        // Default school information if none found
        if (!$sekolah) {
            $sekolah = Sekolah::where('nama_sekolah', 'like', '%Permata Insani%')
                ->first() ?? new \stdClass();
            
            // Jika tidak ada sekolah yang cocok, buat objek dummy dengan data default
            if (!($sekolah->exists ?? false)) {
                $sekolah = new \stdClass();
                $sekolah->nama_sekolah = 'SD IT Permata Insani Islamic School';
                $sekolah->alamat = 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi';
                $sekolah->telepon = '0811742088';
                $sekolah->email = 'permatainsani.sch@gmail.com';
            }
        }

        // 🔄 Semua sekolah untuk filter
        $semuaSekolah = Sekolah::orderBy('nama_sekolah')->get();

        // ✅ Hanya ambil kelas tingkat 6
        $semuaKelas = Kelas::with('sekolah')
            ->where('tingkat', 6)
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.kelulusan', compact(
            'kelulusan',
            'sekolah',
            'tanggalLaporan',
            'semuaSekolah',
            'semuaKelas'
        ));
    }



    // ✅ Export laporan kelulusan ke Excel
    public function exportExcel(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');

        $kelulusanQuery = RiwayatKelulusan::with(['siswa', 'sekolah', 'kelas', 'tahunAjaran'])
            ->orderBy('tanggal_lulus', 'desc');

        if ($tahunAjaranId) {
            $kelulusanQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $kelulusan = $kelulusanQuery->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Kelulusan');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Nama Siswa');
        $sheet->setCellValue('C3', 'Sekolah');
        $sheet->setCellValue('D3', 'Kelas');
        $sheet->setCellValue('E3', 'Tahun Ajaran');
        $sheet->setCellValue('F3', 'Tanggal Lulus');
        $sheet->setCellValue('G3', 'Keterangan');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($kelulusan as $item) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->siswa->nama ?? '-');
            $sheet->setCellValue('C' . $row, $item->sekolah->nama_sekolah ?? '-');
            $sheet->setCellValue('D' . $row, $item->kelas ? $item->kelas->tingkat . ' ' . $item->kelas->nama_kelas : '-');
            $sheet->setCellValue('E' . $row, $item->tahunAjaran->nama_tahun ?? '-');
            $sheet->setCellValue('F' . $row, Carbon::parse($item->tanggal_lulus)->format('d-m-Y'));
            $sheet->setCellValue('G' . $row, $item->keterangan ?? '-');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Kelulusan_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
