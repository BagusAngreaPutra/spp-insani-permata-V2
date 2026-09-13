<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\KoperasiPenjualan;
use App\Models\Pemasukan;
use App\Models\Pembayaran;
use App\Models\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanTransaksiController extends Controller
{
    public function index(Request $request)
    {
        $rows = $this->transactions($request);
        $transactions = $rows;
        $summary = $this->summary($rows);
        $daftarSekolah = Sekolah::orderBy('nama_sekolah')->get();

        return view('laporan.transaksi', compact('transactions', 'summary', 'daftarSekolah'));
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->transactions($request);
        $summary = $this->summary($rows);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Semua Transaksi');

        $sheet->fromArray([
            ['LAPORAN SEMUA TRANSAKSI'],
            ['Periode', $this->periodLabel($request)],
            ['Jumlah Transaksi', $summary['transactions']],
            ['Tagihan Siswa', $summary['student_bills']],
            ['Koperasi', $summary['cooperative']],
            ['Pemasukan Lainnya', $summary['other_income']],
            ['Nilai Transaksi', $summary['gross']],
            ['Potongan', $summary['discount']],
            ['Total Diterima', $summary['received']],
            [],
            ['No', 'Tanggal', 'Jenis', 'Referensi', 'Siswa / Sumber', 'Sekolah', 'Kelas', 'Rincian', 'Item', 'Nilai', 'Potongan', 'Diterima', 'Metode / Keterangan'],
        ]);

        $line = 12;
        foreach ($rows as $index => $row) {
            $sheet->fromArray([[
                $index + 1,
                $row['date']->format('d/m/Y'),
                $row['type_label'],
                $row['reference'],
                $row['party'],
                $row['school'],
                $row['class'],
                $row['description'],
                $row['item_count'],
                $row['gross'],
                $row['discount'],
                $row['received'],
                trim($row['method'].($row['note'] ? ' - '.$row['note'] : '')),
            ]], null, 'A'.$line++);
        }

        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A11:M11')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A11:M11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534');
        $sheet->getStyle('J12:L'.max(12, $line - 1))->getNumberFormat()->setFormatCode('[$Rp-421] #,##0');
        $sheet->freezePane('A12');
        $sheet->setAutoFilter('A11:M'.max(11, $line - 1));
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->download($spreadsheet, 'laporan-semua-transaksi-'.now()->format('Ymd-His').'.xlsx');
    }

    private function transactions(Request $request): Collection
    {
        $selectedType = $request->string('jenis_transaksi')->toString();
        $rows = collect();

        if ($selectedType === '' || $selectedType === 'tagihan') {
            $query = Pembayaran::with(['siswa.kelas', 'siswa.sekolah', 'tagihan.jenisPembayaran']);
            $this->filterPaymentQuery($query, $request);

            $paymentRows = $query->get()
                ->groupBy(fn ($payment) => $payment->transaction_id ?: ($payment->nomor_kwitansi ?: 'payment-'.$payment->id))
                ->map(function (Collection $payments) {
                    $first = $payments->sortBy('id')->first();
                    $descriptions = $payments->map(function ($payment) {
                        $name = $payment->tagihan?->nama_tagihan_dinamis
                            ?? $payment->tagihan?->nama_tagihan
                            ?? 'Tagihan';
                        $period = $payment->periode ?: $payment->tagihan?->periode;

                        return trim($name.($period ? ' ('.$period.')' : ''));
                    })->filter()->unique()->values();

                    return [
                        'sort_id' => (int) $payments->max('id'),
                        'date' => Carbon::parse($first->tanggal_bayar),
                        'type' => 'tagihan',
                        'type_label' => 'Tagihan Siswa',
                        'reference' => $first->nomor_kwitansi ?: ($first->transaction_id ?: 'Pembayaran #'.$first->id),
                        'party' => trim(($first->siswa?->nama ?? '-').($first->siswa?->nis ? ' · NIS '.$first->siswa->nis : '')),
                        'school' => $first->siswa?->sekolah?->nama_sekolah ?? '-',
                        'class' => $this->classLabel($first->siswa?->kelas),
                        'description' => $descriptions->implode('; '),
                        'item_count' => $payments->count(),
                        'gross' => (float) $payments->sum(fn ($payment) => (float) $payment->jumlah_bayar + (float) $payment->diskon),
                        'discount' => (float) $payments->sum('diskon'),
                        'received' => (float) $payments->sum('jumlah_bayar'),
                        'method' => $payments->pluck('metode_bayar')->filter()->map(fn ($method) => ucfirst($method))->unique()->implode(', ') ?: '-',
                        'note' => $payments->pluck('keterangan')->filter()->unique()->implode('; '),
                    ];
                })->values();

            $rows = $rows->concat($paymentRows);
        }

        if ($selectedType === '' || $selectedType === 'koperasi') {
            $query = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'siswa.sekolah', 'details']);
            $this->filterCooperativeQuery($query, $request);

            $rows = $rows->concat($query->get()->map(function ($sale) {
                $items = $sale->details->map(fn ($detail) => $detail->nama_barang.' × '.$detail->jumlah)->implode('; ');

                return [
                    'sort_id' => (int) $sale->id,
                    'date' => Carbon::parse($sale->tanggal),
                    'type' => 'koperasi',
                    'type_label' => 'Koperasi',
                    'reference' => $sale->kode_transaksi ?: 'Koperasi #'.$sale->id,
                    'party' => trim(($sale->siswa?->nama ?? 'Pembeli umum').($sale->siswa?->nis ? ' · NIS '.$sale->siswa->nis : '')),
                    'school' => $sale->sekolah?->nama_sekolah ?? $sale->siswa?->sekolah?->nama_sekolah ?? '-',
                    'class' => $this->classLabel($sale->siswa?->kelas),
                    'description' => $items ?: 'Penjualan koperasi',
                    'item_count' => (int) $sale->details->sum('jumlah'),
                    'gross' => (float) $sale->total,
                    'discount' => 0.0,
                    'received' => (float) $sale->total,
                    'method' => '-',
                    'note' => (string) ($sale->catatan ?? ''),
                ];
            }));
        }

        if ($selectedType === '' || $selectedType === 'pemasukan') {
            $query = Pemasukan::with('sekolah');
            $this->filterIncomeQuery($query, $request);

            $rows = $rows->concat($query->get()->map(function ($income) {
                return [
                    'sort_id' => (int) $income->id,
                    'date' => Carbon::parse($income->tanggal),
                    'type' => 'pemasukan',
                    'type_label' => 'Pemasukan Lainnya',
                    'reference' => 'PM-'.str_pad((string) $income->id, 6, '0', STR_PAD_LEFT),
                    'party' => $income->sumber ?: '-',
                    'school' => $income->sekolah?->nama_sekolah ?? '-',
                    'class' => '-',
                    'description' => $income->keterangan ?: $income->sumber ?: 'Pemasukan lainnya',
                    'item_count' => 1,
                    'gross' => (float) $income->jumlah,
                    'discount' => 0.0,
                    'received' => (float) $income->jumlah,
                    'method' => '-',
                    'note' => '',
                ];
            }));
        }

        return $rows->sort(function (array $left, array $right) {
            $dateCompare = $right['date']->timestamp <=> $left['date']->timestamp;

            return $dateCompare !== 0 ? $dateCompare : ($right['sort_id'] <=> $left['sort_id']);
        })->values();
    }

    private function filterPaymentQuery($query, Request $request): void
    {
        if ($request->filled('sekolah_id')) {
            $query->whereHas('siswa', fn ($student) => $student->where('id_sekolah', $request->sekolah_id));
        }
        $this->applyDateRange($query, 'tanggal_bayar', $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($nested) use ($search) {
                $nested->where('nomor_kwitansi', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('siswa', fn ($student) => $student->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                    ->orWhereHas('tagihan', fn ($bill) => $bill->where('nama_tagihan', 'like', "%{$search}%")->orWhere('periode', 'like', "%{$search}%"));
            });
        }
    }

    private function filterCooperativeQuery($query, Request $request): void
    {
        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }
        $this->applyDateRange($query, 'tanggal', $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($nested) use ($search) {
                $nested->where('kode_transaksi', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('siswa', fn ($student) => $student->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                    ->orWhereHas('details', fn ($detail) => $detail->where('nama_barang', 'like', "%{$search}%"));
            });
        }
    }

    private function filterIncomeQuery($query, Request $request): void
    {
        if ($request->filled('sekolah_id')) {
            $query->where('sekolah_id', $request->sekolah_id);
        }
        $this->applyDateRange($query, 'tanggal', $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(fn ($nested) => $nested->where('sumber', 'like', "%{$search}%")->orWhere('keterangan', 'like', "%{$search}%"));
        }
    }

    private function applyDateRange($query, string $column, Request $request): void
    {
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate($column, '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate($column, '<=', $request->tanggal_selesai);
        }
    }

    private function summary(Collection $rows): array
    {
        return [
            'transactions' => $rows->count(),
            'student_bills' => $rows->where('type', 'tagihan')->count(),
            'cooperative' => $rows->where('type', 'koperasi')->count(),
            'other_income' => $rows->where('type', 'pemasukan')->count(),
            'gross' => (float) $rows->sum('gross'),
            'discount' => (float) $rows->sum('discount'),
            'received' => (float) $rows->sum('received'),
        ];
    }

    private function classLabel($class): string
    {
        if (! $class) {
            return '-';
        }

        return trim(($class->tingkat !== null ? 'Tingkat '.$class->tingkat : '').' '.($class->nama_kelas ?? '')) ?: '-';
    }

    private function periodLabel(Request $request): string
    {
        if (! $request->filled('tanggal_mulai') && ! $request->filled('tanggal_selesai')) {
            return 'Semua tanggal';
        }

        return ($request->tanggal_mulai ?: 'awal').' s.d. '.($request->tanggal_selesai ?: 'sekarang');
    }

    private function download(Spreadsheet $spreadsheet, string $filename)
    {
        $path = tempnam(sys_get_temp_dir(), 'laporan-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
