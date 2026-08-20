<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengeluaran; // pastikan sudah ada model Pengeluaran
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Sekolah;

class LaporanPengeluaranController extends Controller
{
    public function index(Request $request)
    {
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        $daftarSekolah = Sekolah::all();
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        $pengeluarans = Pengeluaran::query();

        if ($request->filled('sekolah_id')) {
            $pengeluarans->where('sekolah_id', $request->sekolah_id);
        }

        $pengeluarans = $pengeluarans->get();

        return view('laporan.pengeluaran', compact('sekolah', 'pengeluarans', 'tanggalLaporan', 'daftarSekolah'));
    }


    public function exportExcel()
    {
        $pengeluarans = Pengeluaran::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Pengeluaran');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Tanggal');
        $sheet->setCellValue('C3', 'Jumlah');
        $sheet->setCellValue('D3', 'Keperluan');
        $sheet->setCellValue('E3', 'Keterangan');
        $sheet->setCellValue('F3', 'Sekolah ID');
        $sheet->getStyle('A3:F3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($pengeluarans as $p) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $p->tanggal);
            $sheet->setCellValue('C'.$row, $p->jumlah);
            $sheet->setCellValue('D'.$row, $p->keperluan ?? '-');
            $sheet->setCellValue('E'.$row, $p->keterangan ?? '-');
            $sheet->setCellValue('F'.$row, $p->sekolah_id);
            $row++;
        }

        foreach (range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Pengeluaran_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
