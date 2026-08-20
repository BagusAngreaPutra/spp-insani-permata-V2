<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemasukan; // pastikan modelnya ada
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Sekolah;

class LaporanPemasukanController extends Controller
{
    public function index()
    {
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        $daftarSekolah = Sekolah::all();
        $pemasukans = Pemasukan::with('sekolah')
            ->when(request('sekolah_id'), function ($query) {
                $query->where('sekolah_id', request('sekolah_id'));
            })
             ->latest()->paginate(10);
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.pemasukan', compact('sekolah','pemasukans','tanggalLaporan', 'daftarSekolah'));
    }

    public function exportExcel()
    {
        $pemasukans = Pemasukan::all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Pemasukan');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Tanggal');
        $sheet->setCellValue('C3', 'Jumlah');
        $sheet->setCellValue('D3', 'Sumber');
        $sheet->setCellValue('E3', 'Keterangan');
        $sheet->setCellValue('F3', 'Sekolah ID');
        $sheet->getStyle('A3:F3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($pemasukans as $p) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $p->tanggal);
            $sheet->setCellValue('C'.$row, $p->jumlah);
            $sheet->setCellValue('D'.$row, $p->sumber ?? '-');
            $sheet->setCellValue('E'.$row, $p->keterangan ?? '-');
            $sheet->setCellValue('F'.$row, $p->sekolah_id);
            $row++;
        }

        foreach (range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Pemasukan_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
