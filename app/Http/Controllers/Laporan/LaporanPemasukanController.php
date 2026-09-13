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
    public function index(Request $request)
    {
        $sekolah = [
            'nama'   => 'SD IT Permata Insani Islamic School',
            'alamat' => 'Jl. Abdul Muis No. 27, Kel. Lingkar Selatan, Kec. Paal Merah, Kota Jambi, Jambi',
            'telepon'=> '0811742088',
            'email'  => 'permatainsani.sch@gmail.com'
        ];

        $daftarSekolah = Sekolah::orderBy('nama_sekolah')->get();
        $filteredIncome = $this->queryPemasukan($request)->latest('tanggal')->latest('id')->get();
        $reportSummary = [
            'transactions' => $filteredIncome->count(),
            'sources' => $filteredIncome->pluck('sumber')->filter()->map(fn ($source) => mb_strtolower(trim($source)))->unique()->count(),
            'total' => (float) $filteredIncome->sum('jumlah'),
            'average' => $filteredIncome->count() > 0 ? (float) $filteredIncome->avg('jumlah') : 0,
        ];
        $pemasukans = $this->queryPemasukan($request)->latest('tanggal')->latest('id')->paginate(20)->withQueryString();
        $tanggalLaporan = Carbon::now()->translatedFormat('d F Y');

        return view('laporan.pemasukan', compact('sekolah', 'pemasukans', 'reportSummary', 'tanggalLaporan', 'daftarSekolah'));
    }

    public function exportExcel(Request $request)
    {
        $pemasukans = $this->queryPemasukan($request)->latest('tanggal')->latest('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'LAPORAN PEMASUKAN LAINNYA');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header
        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Tanggal');
        $sheet->setCellValue('C3', 'Jumlah');
        $sheet->setCellValue('D3', 'Sumber');
        $sheet->setCellValue('E3', 'Keterangan');
        $sheet->setCellValue('F3', 'Sekolah');
        $sheet->getStyle('A3:F3')->getFont()->setBold(true);

        // Isi data
        $row = 4;
        $no = 1;
        foreach ($pemasukans as $p) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, optional($p->tanggal)->format('d/m/Y'));
            $sheet->setCellValue('C'.$row, $p->jumlah);
            $sheet->setCellValue('D'.$row, $p->sumber ?? '-');
            $sheet->setCellValue('E'.$row, $p->keterangan ?? '-');
            $sheet->setCellValue('F'.$row, $p->sekolah->nama_sekolah ?? '-');
            $row++;
        }

        $sheet->setCellValue('A'.$row, 'TOTAL');
        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->setCellValue('C'.$row, $pemasukans->sum('jumlah'));
        $sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
        $sheet->getStyle('C4:C'.$row)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A','F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Laporan_Pemasukan_Lainnya_'.Carbon::now()->format('Ymd_His').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    private function queryPemasukan(Request $request)
    {
        return Pemasukan::with('sekolah')
            ->when($request->filled('sekolah_id'), fn ($query) => $query->where('sekolah_id', $request->sekolah_id))
            ->when($request->filled('tanggal_mulai'), fn ($query) => $query->whereDate('tanggal', '>=', $request->tanggal_mulai))
            ->when($request->filled('tanggal_selesai'), fn ($query) => $query->whereDate('tanggal', '<=', $request->tanggal_selesai))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('sumber', 'like', '%' . $search . '%')
                        ->orWhere('keterangan', 'like', '%' . $search . '%')
                        ->orWhereHas('sekolah', fn ($schoolQuery) => $schoolQuery->where('nama_sekolah', 'like', '%' . $search . '%'));
                });
            });
    }
}
