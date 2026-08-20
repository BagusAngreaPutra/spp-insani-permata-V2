<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanAdminController extends Controller
{
    /**
     * Menampilkan halaman laporan admin (print).
     */
    public function index()
    {
        // Identitas sekolah
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        // Data admin
        $admins = Admin::all();

        // Tanggal laporan
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.admin', compact('sekolah', 'admins', 'tanggalLaporan'));
    }

    /**
     * Export laporan admin ke Excel.
     */
    public function exportExcel()
    {
        $admins = Admin::all();

        // Buat Spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Admin');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Nama Admin');
        $sheet->setCellValue('C3', 'Username');
        $sheet->setCellValue('D3', 'Role');
        $sheet->setCellValue('E3', 'Dibuat Pada');
        $sheet->getStyle('A3:E3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($admins as $admin) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $admin->nama_admin);
            $sheet->setCellValue('C'.$row, $admin->username);
            $sheet->setCellValue('D'.$row, $admin->role);
            $sheet->setCellValue('E'.$row, $admin->created_at ? $admin->created_at->format('d-m-Y H:i') : '-');
            $row++;
        }

        // Auto width
        foreach(range('A','E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Nama file
        $filename = 'Laporan_Admin_'.Carbon::now()->format('Ymd_His').'.xlsx';

        // Simpan ke memory dan kirim response download
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
