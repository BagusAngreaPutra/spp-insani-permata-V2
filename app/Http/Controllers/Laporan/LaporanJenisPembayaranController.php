<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisPembayaran; // pastikan model ini ada
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanJenisPembayaranController extends Controller
{
    public function index()
    {
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        $jenis_pembayarans = JenisPembayaran::with(['sekolah', 'siswa', 'kelas'])->get();
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.jenis_pembayaran', compact('sekolah','jenis_pembayarans','tanggalLaporan'));
    }

    public function exportExcel()
    {
        $jenis_pembayarans = JenisPembayaran::with(['sekolah', 'siswa', 'kelas'])->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Data Jenis Pembayaran');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Nama Sekolah');
        $sheet->setCellValue('C3', 'Nama Pembayaran');
        $sheet->setCellValue('D3', 'Tipe');
        $sheet->setCellValue('E3', 'Nominal');
        $sheet->setCellValue('F3', 'Jatuh Tempo');
        $sheet->setCellValue('G3', 'Target');
        $sheet->setCellValue('H3', 'Dibuat Pada');
        $sheet->getStyle('A3:H3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($jenis_pembayarans as $jp) {
            $target = '';
            if ($jp->target_type == 'all') {
                $target = 'Semua Siswa';
            } elseif ($jp->target_type == 'specific_students') {
                $target = 'Siswa Tertentu (' . $jp->siswa->count() . ' siswa)';
            } elseif ($jp->target_type == 'specific_classes') {
                $target = 'Kelas Tertentu (' . $jp->kelas->count() . ' kelas)';
            }

            // Format jatuh tempo berdasarkan tipe
            $jatuhTempo = '-';
            if ($jp->jatuh_tempo) {
                if ($jp->tipe == 'bulanan') {
                    $jatuhTempo = 'Tanggal ' . Carbon::parse($jp->jatuh_tempo)->format('d');
                } else {
                    $jatuhTempo = Carbon::parse($jp->jatuh_tempo)->format('d-m-Y');
                }
            }

            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $jp->sekolah->nama_sekolah ?? '-');
            $sheet->setCellValue('C'.$row, $jp->nama_pembayaran);
            $sheet->setCellValue('D'.$row, $jp->tipe);
            $sheet->setCellValue('E'.$row, $jp->nominal);
            $sheet->setCellValue('F'.$row, $jatuhTempo);
            $sheet->setCellValue('G'.$row, $target);
            $sheet->setCellValue('H'.$row, $jp->created_at ? $jp->created_at->format('d-m-Y H:i') : '-');
            $row++;
        }

        foreach (range('A','H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Jenis_Pembayaran_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
