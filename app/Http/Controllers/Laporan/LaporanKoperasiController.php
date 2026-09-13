<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KoperasiPenjualan;
use App\Models\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanKoperasiController extends Controller
{
    public function index(Request $request)
    {
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');
        $daftarSekolah = Sekolah::orderBy('nama_sekolah')->get();
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

        $penjualan = $this->queryPenjualan($request)->latest('tanggal')->latest('id')->get();
        $totalPenjualan = $penjualan->sum('total');
        $totalTransaksi = $penjualan->count();
        $totalBarangTerjual = $penjualan->sum(fn ($item) => $item->details->sum('jumlah'));
        $totalSiswa = $penjualan->pluck('siswa_id')->filter()->unique()->count();
        $jenisBarangTerjual = $penjualan->flatMap(fn ($item) => $item->details)->pluck('koperasi_id')->filter()->unique()->count();
        $rataRataTransaksi = $totalTransaksi > 0 ? $totalPenjualan / $totalTransaksi : 0;

        return view('laporan.koperasi', compact(
            'penjualan',
            'tanggalLaporan',
            'daftarSekolah',
            'daftarKelas',
            'totalPenjualan',
            'totalTransaksi',
            'totalBarangTerjual',
            'totalSiswa',
            'jenisBarangTerjual',
            'rataRataTransaksi'
        ));
    }

    public function exportExcel(Request $request)
    {
        $penjualan = $this->queryPenjualan($request)->latest('tanggal')->latest('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN PENJUALAN KOPERASI');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A2', 'SD IT PERMATA INSANI');
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A3', 'Tanggal Cetak: ' . Carbon::now()->translatedFormat('d F Y'));
        $sheet->mergeCells('A3:K3');

        $sheet->setCellValue('A5', 'Total Transaksi');
        $sheet->setCellValue('B5', $penjualan->count());
        $sheet->setCellValue('D5', 'Total Barang Terjual');
        $sheet->setCellValue('E5', $penjualan->sum(fn ($item) => $item->details->sum('jumlah')));
        $sheet->setCellValue('G5', 'Total Penjualan');
        $sheet->setCellValue('H5', $penjualan->sum('total'));
        $sheet->getStyle('A5:H5')->getFont()->setBold(true);

        $headerRow = 7;
        $headers = [
            'A' => 'No',
            'B' => 'Tanggal',
            'C' => 'Kode Transaksi',
            'D' => 'Nama Siswa',
            'E' => 'NIS',
            'F' => 'Kelas',
            'G' => 'Sekolah',
            'H' => 'Barang',
            'I' => 'Jumlah Item',
            'J' => 'Total',
            'K' => 'Catatan',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column . $headerRow, $label);
        }

        $sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');
        $sheet->getStyle('A' . $headerRow . ':K' . $headerRow)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = $headerRow + 1;
        foreach ($penjualan as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, optional($item->tanggal)->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $item->kode_transaksi);
            $sheet->setCellValue('D' . $row, $item->siswa->nama ?? '-');
            $sheet->setCellValue('E' . $row, $item->siswa->nis ?? '-');
            $sheet->setCellValue('F' . $row, $item->siswa->kelas->kelas ?? '-');
            $sheet->setCellValue('G' . $row, $item->sekolah->nama_sekolah ?? '-');
            $sheet->setCellValue('H' . $row, $item->details->map(fn ($detail) => $detail->nama_barang . ' (' . $detail->jumlah . ')')->implode(', '));
            $sheet->setCellValue('I' . $row, $item->details->sum('jumlah'));
            $sheet->setCellValue('J' . $row, $item->total);
            $sheet->setCellValue('K' . $row, $item->catatan ?: '-');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue('I' . $row, 'Total');
        $sheet->setCellValue('J' . $row, $penjualan->sum('total'));
        $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'Laporan_Koperasi_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function queryPenjualan(Request $request)
    {
        $query = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'details']);

        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal', [
                Carbon::parse($request->tanggal_mulai)->toDateString(),
                Carbon::parse($request->tanggal_selesai)->toDateString(),
            ]);
        } elseif ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_transaksi', 'like', '%' . $search . '%')
                    ->orWhereHas('siswa', function ($siswaQuery) use ($search) {
                        $siswaQuery->where('nama', 'like', '%' . $search . '%')
                            ->orWhere('nis', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('details', function ($detailQuery) use ($search) {
                        $detailQuery->where('nama_barang', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }
}
