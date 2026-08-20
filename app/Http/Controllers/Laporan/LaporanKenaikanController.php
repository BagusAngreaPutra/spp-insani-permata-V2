<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sekolah;
use App\Models\Kelas;
use App\Models\RiwayatKenaikan;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanKenaikanController extends Controller
{
    // ✅ Menampilkan halaman laporan kenaikan
    public function index(Request $request)
    {
        $sekolahId     = $request->get('sekolah_id');
        $kelasId       = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $search        = $request->get('search');

        if ($sekolahId && $kelasId
            && !Kelas::whereKey($kelasId)->where('sekolah_id', $sekolahId)->exists()) {
            $kelasId = null;
            $request->merge(['kelas_id' => null]);
        }

        $riwayatQuery = RiwayatKenaikan::with(['siswa.sekolah', 'kelasAwal', 'kelasBaru', 'tahunAjaran'])
            ->orderBy('tanggal_kenaikan', 'desc');

        // Filter berdasarkan sekolah atau search
        if ($sekolahId || $search) {
            $riwayatQuery->whereHas('siswa', function ($q) use ($sekolahId, $search) {
                if ($sekolahId) {
                    $q->where('id_sekolah', $sekolahId);
                }
                if ($search) {
                    $q->where('nama', 'like', '%' . $search . '%');
                }
            });
        }

        // Filter berdasarkan kelas awal atau kelas baru
        if ($kelasId) {
            $riwayatQuery->where(function ($q) use ($kelasId) {
                $q->where('kelas_awal_id', $kelasId)
                  ->orWhere('kelas_baru_id', $kelasId);
            });
        }

        // Filter berdasarkan tahun ajaran
        if ($tahunAjaranId) {
            $riwayatQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $riwayat = $riwayatQuery->get();

        // Dropdown data
        $daftarSekolah     = Sekolah::all();
        $daftarKelas = Kelas::with('sekolah')
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();
        $daftarTahunAjaran = TahunAjaran::validPeriods();

        // Identitas sekolah di header
        if ($sekolahId) {
            $dataSekolah = Sekolah::find($sekolahId);
            $sekolah = [
                'nama'    => $dataSekolah->nama_sekolah ?? '-',
                'alamat'  => $dataSekolah->alamat ?? '-',
                'telepon' => $dataSekolah->telepon ?? '-',
                'email'   => $dataSekolah->email ?? '-',
            ];
        } else {
            $sekolah = [
                'nama'   => 'SD IT Permata Insani Islamic School',
                'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
                'telepon'=> '0811742088',
                'email'  => 'permatainsani.sch@gmail.com'
            ];
        }

        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.kenaikan', compact(
            'riwayat',
            'sekolah',
            'tanggalLaporan',
            'daftarSekolah',
            'daftarKelas',
            'daftarTahunAjaran',
            'sekolahId',
            'kelasId',
            'tahunAjaranId',
            'search'
        ));
    }




    // ✅ Export laporan kenaikan ke Excel
    public function exportExcel(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');

        $riwayatQuery = RiwayatKenaikan::with(['siswa','kelasAwal','kelasBaru','tahunAjaran'])
            ->orderBy('tanggal_kenaikan', 'desc');

        if ($tahunAjaranId) {
            $riwayatQuery->where('tahun_ajaran_id', $tahunAjaranId);
        }

        $riwayat = $riwayatQuery->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Kenaikan Kelas');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Nama Siswa');
        $sheet->setCellValue('C3', 'Kelas Awal');
        $sheet->setCellValue('D3', 'Tingkat Awal');
        $sheet->setCellValue('E3', 'Kelas Baru');
        $sheet->setCellValue('F3', 'Tingkat Baru');
        $sheet->setCellValue('G3', 'Tahun Ajaran');
        $sheet->setCellValue('H3', 'Tanggal Kenaikan');
        $sheet->getStyle('A3:H3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($riwayat as $item) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $item->siswa->nama ?? '-');
            $sheet->setCellValue('C'.$row, $item->kelasAwal->nama_kelas ?? '-');
            $sheet->setCellValue('D'.$row, $item->kelasAwal->tingkat ?? '-');
            $sheet->setCellValue('E'.$row, $item->kelasBaru->nama_kelas ?? '-');
            $sheet->setCellValue('F'.$row, $item->kelasBaru->tingkat ?? '-');
            $sheet->setCellValue('G'.$row, $item->tahunAjaran->nama_tahun ?? '-');
            $sheet->setCellValue('H'.$row, Carbon::parse($item->tanggal_kenaikan)->format('d-m-Y'));
            $row++;
        }

        foreach (range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Kenaikan_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
