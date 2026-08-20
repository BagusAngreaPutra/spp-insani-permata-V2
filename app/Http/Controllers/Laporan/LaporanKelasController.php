<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanKelasController extends Controller
{
    public function index(Request $request)
    {
        // Kop sekolah di header laporan
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        // 🔹 Ambil daftar sekolah dan tahun ajaran untuk dropdown filter
        $daftarSekolah = Sekolah::all();
        $daftarTahun   = TahunAjaran::validPeriods();

        // 🔹 Base query
        $query = Kelas::with(['sekolah', 'tahunAjaran']);

        // 🔹 Filter sekolah jika dipilih
        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }

        // 🔹 Filter tahun ajaran jika dipilih
        if ($request->filled('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        // 🔹 Urutkan
        $kelas = $query->orderBy('tingkat', 'asc')->get();

        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.kelas', compact(
            'sekolah',
            'kelas',
            'tanggalLaporan',
            'daftarSekolah',
            'daftarTahun'
        ));
    }

    public function exportExcel(Request $request)
    {
        // 🔹 Base query untuk export
        $query = Kelas::with(['sekolah', 'tahunAjaran']);

        // 🔹 Terapkan filter jika ada
        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }
        if ($request->filled('tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $request->tahun_ajaran_id);
        }

        $kelas = $query->orderBy('tingkat', 'asc')->get();

        // 🔹 Buat spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Kelas');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header kolom
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Kelas');
        $sheet->setCellValue('C3', 'Nama Sekolah');
        $sheet->setCellValue('D3', 'Tahun Ajaran');
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($kelas as $k) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $k->kelas);
            $sheet->setCellValue('C'.$row, $k->sekolah->nama_sekolah ?? '-');
            $sheet->setCellValue('D'.$row, $k->tahunAjaran->nama_tahun ?? '-');
            $row++;
        }

        // Auto-size
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // File sementara & download
        $filename = 'Laporan_Kelas_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
