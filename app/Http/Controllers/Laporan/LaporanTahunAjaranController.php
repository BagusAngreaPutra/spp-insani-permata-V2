<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TahunAjaran; // pastikan model TahunAjaran sudah ada
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanTahunAjaranController extends Controller
{
    public function index()
    {
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        $tahunAjarans = TahunAjaran::all();
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.tahun_ajaran', compact('sekolah','tahunAjarans','tanggalLaporan'));
    }

    public function exportExcel()
    {
        $tahunAjarans = TahunAjaran::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Tahun Ajaran');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Nama Tahun');
        $sheet->setCellValue('C3', 'Aktif');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($tahunAjarans as $t) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $t->nama_tahun);
            $sheet->setCellValue('C'.$row, $t->aktif ? 'Ya' : 'Tidak');
            $row++;
        }

        foreach (range('A','C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Tahun_Ajaran_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
