<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Sekolah;
use App\Models\Kelas;  // pastikan modelnya sudah ada
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanPembayaranController extends Controller
{
    public function index(Request $request)
    {
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        // Ambil data sekolah untuk filter
        $daftarSekolah = Sekolah::all();
        $daftarKelas = Kelas::with('sekolah')
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        if ($request->filled('sekolah_id') && $request->filled('kelas_id')
            && !$daftarKelas->contains(fn ($item) => (string) $item->id === (string) $request->kelas_id
                && (string) $item->sekolah_id === (string) $request->sekolah_id)) {
            $request->merge(['kelas_id' => null]);
        }

        // Query awal
        $query = Pembayaran::with(['siswa.kelas', 'siswa.sekolah']);

        // Filter berdasarkan request
        if ($request->filled('sekolah_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('id_sekolah', $request->sekolah_id);
            });
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_bayar', [
                Carbon::parse($request->tanggal_mulai)->startOfDay(),
                Carbon::parse($request->tanggal_selesai)->endOfDay()
            ]);
        } elseif ($request->filled('tanggal')) {
            $query->whereDate('tanggal_bayar', $request->tanggal);
        }


        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('search')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            });
        }

        $pembayarans = $query->latest()->get();
        
        // Hitung subtotal berdasarkan metode pembayaran
        $subtotal = $pembayarans->groupBy('metode_bayar')->map(function ($items) {
            return $items->sum('jumlah_bayar');
        });

        $sekolah = [
            'nama'   => 'SD IT Permata Insani',
            'alamat' => 'Jl. Kenanga No. 123, Kota Depok, Jawa Barat',
            'telepon'=> '(021) 1234567',
            'email'  => 'info@permatainsani.sch.id'
        ];

        return view('laporan.pembayaran', compact('pembayarans', 'subtotal', 'tanggalLaporan', 'sekolah', 'daftarSekolah', 'daftarKelas'));
    }

    public function exportExcel(Request $request)
    {
        // Apply same filters as index method
        $query = Pembayaran::with(['siswa.kelas', 'siswa.sekolah']);

        // Filter berdasarkan request
        if ($request->filled('sekolah_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('id_sekolah', $request->sekolah_id);
            });
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        // Filter berdasarkan rentang tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_bayar', [
                Carbon::parse($request->tanggal_mulai)->startOfDay(),
                Carbon::parse($request->tanggal_selesai)->endOfDay()
            ]);
        } elseif ($request->filled('tanggal')) {
            $query->whereDate('tanggal_bayar', $request->tanggal);
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->metode_bayar);
        }

        if ($request->filled('search')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            });
        }

        $pembayarans = $query->latest()->get();
        
        // Hitung subtotal berdasarkan metode pembayaran
        $subtotal = $pembayarans->groupBy('metode_bayar')->map(function ($items) {
            return $items->sum('jumlah_bayar');
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'LAPORAN DATA PEMBAYARAN');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Info Sekolah
        $sheet->setCellValue('A2', 'SD IT PERMATA INSANI');
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A3', 'Jl. Kenanga No. 123, Kota Depok, Jawa Barat');
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // Tanggal cetak
        $sheet->setCellValue('A5', 'Tanggal Cetak: ' . Carbon::now()->translatedFormat('d F Y'));
        $sheet->mergeCells('A5:J5');
        
        // Periode filter jika ada
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $sheet->setCellValue('A6', 'Periode: ' . Carbon::parse($request->tanggal_mulai)->format('d/m/Y') . ' - ' . Carbon::parse($request->tanggal_selesai)->format('d/m/Y'));
            $sheet->mergeCells('A6:J6');
        }

        // Subtotal berdasarkan metode pembayaran
        $rowSubtotal = 7;
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $rowSubtotal = 8;
        }
        
        $sheet->setCellValue('A'.$rowSubtotal, 'SUBTOTAL BERDASARKAN METODE PEMBAYARAN');
        $sheet->mergeCells('A'.$rowSubtotal.':J'.$rowSubtotal);
        $sheet->getStyle('A'.$rowSubtotal)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A'.$rowSubtotal)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');
        
        $rowSubtotal++;
        $sheet->setCellValue('A'.$rowSubtotal, 'Tunai');
        $sheet->setCellValue('B'.$rowSubtotal, 'Rp ' . number_format($subtotal['tunai'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('D'.$rowSubtotal, 'Transfer');
        $sheet->setCellValue('E'.$rowSubtotal, 'Rp ' . number_format($subtotal['transfer'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('G'.$rowSubtotal, 'KJC');
        $sheet->setCellValue('H'.$rowSubtotal, 'Rp ' . number_format($subtotal['kjc'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('I'.$rowSubtotal, 'Tabungan');
        $sheet->setCellValue('J'.$rowSubtotal, 'Rp ' . number_format($subtotal['tabungan'] ?? 0, 0, ',', '.'));
        
        $sheet->getStyle('A'.$rowSubtotal.':J'.$rowSubtotal)->getFont()->setBold(true);
        foreach (['B', 'E', 'H', 'J'] as $column) {
            $sheet->getStyle($column.$rowSubtotal)->getFont()->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN);
        }

        // Header tabel
        $headerRow = $rowSubtotal + 2;
        $sheet->setCellValue('A'.$headerRow, 'No');
        $sheet->setCellValue('B'.$headerRow, 'Nama Siswa');
        $sheet->setCellValue('C'.$headerRow, 'Kelas');
        $sheet->setCellValue('D'.$headerRow, 'Jumlah Bayar');
        $sheet->setCellValue('E'.$headerRow, 'Tanggal Bayar');
        $sheet->setCellValue('F'.$headerRow, 'Metode Bayar');
        $sheet->setCellValue('G'.$headerRow, 'Keterangan');
        $sheet->setCellValue('H'.$headerRow, 'Cicilan Ke');
        $sheet->setCellValue('I'.$headerRow, 'Total Cicilan');
        $sheet->setCellValue('J'.$headerRow, 'Subtotal');
        
        // Style header
        $sheet->getStyle('A'.$headerRow.':J'.$headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$headerRow.':J'.$headerRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');
        $sheet->getStyle('A'.$headerRow.':J'.$headerRow)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Isi data
        $row = $headerRow + 1;
        $no = 1;
        $totalBayar = 0;
        
        foreach ($pembayarans as $p) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $p->siswa->nama ?? '-');
            $sheet->setCellValue('C'.$row, ($p->siswa->kelas->tingkat ?? '-') . ' ' . ($p->siswa->kelas->nama_kelas ?? ''));
            $sheet->setCellValue('D'.$row, $p->jumlah_bayar);
            $sheet->setCellValue('E'.$row, Carbon::parse($p->tanggal_bayar)->format('d/m/Y'));
            $sheet->setCellValue('F'.$row, ucfirst($p->metode_bayar));
            $sheet->setCellValue('G'.$row, $p->keterangan ?? '-');
            $sheet->setCellValue('H'.$row, $p->cicilan_ke ?? '-');
            $sheet->setCellValue('I'.$row, $p->total_cicilan ?? '-');
            $sheet->setCellValue('J'.$row, 'Rp ' . number_format($subtotal[$p->metode_bayar] ?? 0, 0, ',', '.'));
            
            // Format currency untuk jumlah bayar
            $sheet->getStyle('D'.$row)->getNumberFormat()
                ->setFormatCode('#,##0');
            
            // Borders
            $sheet->getStyle('A'.$row.':J'.$row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $totalBayar += $p->jumlah_bayar;
            $row++;
        }

        // Total
        $sheet->setCellValue('A'.$row, 'TOTAL');
        $sheet->mergeCells('A'.$row.':C'.$row);
        $sheet->setCellValue('D'.$row, $totalBayar);
        $sheet->getStyle('A'.$row.':J'.$row)->getFont()->setBold(true);
        $sheet->getStyle('D'.$row)->getNumberFormat()
            ->setFormatCode('#,##0');
        $sheet->getStyle('A'.$row.':J'.$row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');
        $sheet->getStyle('A'.$row.':J'.$row)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Auto size columns
        foreach (range('A','J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Pembayaran_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
}
