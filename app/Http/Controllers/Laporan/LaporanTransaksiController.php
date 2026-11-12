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
    private function funds($payments): array
    {
        $funds = array_fill_keys(['tunai', 'transfer', 'kjc', 'tabungan', 'belum'], 0.0);
        foreach ($payments as $payment) {
            $method = strtolower(trim((string) $payment->metode_bayar));
            $funds[array_key_exists($method, $funds) ? $method : 'belum'] += (float) $payment->jumlah_bayar;
        }
        return $funds;
    }

    public function index(Request $request)
    {
        $index = $this->transactionIndex($request);
        $aggregate = (clone $index)->selectRaw('COUNT(*) AS transactions, COALESCE(SUM(gross), 0) AS gross, COALESCE(SUM(discount), 0) AS discount, COALESCE(SUM(received), 0) AS received')
            ->selectRaw("COALESCE(SUM(CASE WHEN source = 'tagihan' THEN 1 ELSE 0 END), 0) AS student_bills, COALESCE(SUM(CASE WHEN source = 'koperasi' THEN 1 ELSE 0 END), 0) AS cooperative, COALESCE(SUM(CASE WHEN source = 'pemasukan' THEN 1 ELSE 0 END), 0) AS other_income");
        foreach (['tunai', 'transfer', 'kjc', 'tabungan', 'belum'] as $method) $aggregate->selectRaw("COALESCE(SUM(fund_{$method}), 0) AS fund_{$method}");
        $summary = (array) $aggregate->first();
        $summary['funds'] = [];
        foreach (['tunai', 'transfer', 'kjc', 'tabungan', 'belum'] as $method) $summary['funds'][$method] = (float) $summary['fund_'.$method];
        $sort = $request->input('sort', 'date');
        $index->orderBy(in_array($sort, ['date', 'reference', 'party', 'gross', 'discount', 'received', 'sort_id'], true) ? $sort : 'date', \App\Support\ServerTable::direction($request, 'desc'))
            ->orderByDesc('date')->orderByDesc('sort_id')->orderBy('source')->orderBy('row_key');
        $transactions = \App\Support\ServerTable::paginate($index, $request);
        $keys = $transactions->getCollection();
        $details = $this->transactions($request, $keys)->keyBy(fn ($row) => $row['type'].'-'.$row['sort_id']);
        $transactions->setCollection($keys->map(fn ($key) => $details->get($key->source.'-'.$key->sort_id))->filter()->values());
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
            ['No', 'Tanggal', 'Referensi / Jenis', 'Siswa / Sumber', 'Rincian Pembayaran', 'Nilai', 'Potongan', 'Diterima', 'Metode'],
        ]);

        $line = 12;
        foreach ($rows as $index => $row) {
            $sheet->fromArray([[
                $index + 1,
                $row['date']->format('d/m/Y'),
                $row['reference']."\n".$row['type_label'],
                $row['party']."\n".$row['school'].($row['class'] !== '-' ? ' / '.$row['class'] : ''),
                $row['description']."\n".$row['item_count'].' item'.($row['note'] ? "\nCatatan: ".$row['note'] : ''),
                $row['gross'],
                $row['discount'],
                $row['received'],
                $row['method'],
            ]], null, 'A'.$line++);
        }

        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A11:I11')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A11:I11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF166534');
        $sheet->getStyle('F12:H'.max(12, $line))->getNumberFormat()->setFormatCode('[$Rp-421] #,##0');
        $sheet->freezePane('A12');
        $sheet->setAutoFilter('A11:I'.max(11, $line - 1));
        $sheet->fromArray([['TOTAL', $summary['gross'], $summary['discount'], $summary['received']]], null, 'E'.$line);
        $sheet->getStyle('E'.$line.':H'.$line)->getFont()->setBold(true);
        foreach (['A'=>5, 'B'=>13, 'C'=>25, 'D'=>34, 'E'=>48, 'F'=>18, 'G'=>16, 'H'=>18, 'I'=>14] as $column=>$width) { $sheet->getColumnDimension($column)->setWidth($width); }
        $sheet->getStyle('A11:I'.$line)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('F12:H'.$line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $fundSheet = $spreadsheet->createSheet()->setTitle('Jenis Dana');
        $fundSheet->setCellValue('A1', 'RINCIAN KAS MASUK BERDASARKAN JENIS DANA');
        $fundSheet->mergeCells('A1:H1');
        $fundSheet->setCellValue('A2', 'Periode: '.$this->periodLabel($request));
        $fundSheet->mergeCells('A2:H2');
        $fundSheet->fromArray([['Tanggal', 'Referensi', 'Siswa / Sumber', 'TN / Tunai', 'TF / Transfer', 'KJC', 'Tabungan', 'Belum dicatat']], null, 'A4');
        foreach ($rows as $index => $row) {
            $fundSheet->fromArray([array_merge([$row['date']->format('d/m/Y'), $row['reference'], $row['party']], array_values($row['funds']))], null, 'A'.($index + 5));
        }
        $fundTotalLine = $rows->count() + 5;
        $fundSheet->setCellValue('C'.$fundTotalLine, 'TOTAL');
        $fundSheet->fromArray([array_values($summary['funds'])], null, 'D'.$fundTotalLine);
        $fundSheet->getStyle('A4:H4')->getFont()->setBold(true);
        $fundSheet->getStyle('C'.$fundTotalLine.':H'.$fundTotalLine)->getFont()->setBold(true);
        $fundSheet->getStyle('D5:H'.$fundTotalLine)->getNumberFormat()->setFormatCode('[$Rp-421] #,##0');
        $fundSheet->getStyle('A4:H'.$fundTotalLine)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        foreach (['A'=>13,'B'=>25,'C'=>34,'D'=>18,'E'=>18,'F'=>18,'G'=>18,'H'=>18] as $column=>$width) { $fundSheet->getColumnDimension($column)->setWidth($width); }
        $fundSheet->freezePane('A5');
        $fundSheet->setAutoFilter('A4:H'.max(4, $fundTotalLine - 1));
        $spreadsheet->getDefaultStyle()->getFont()->setName('Manrope')->setSize(10);
        foreach ([[$sheet, 11, 'I', $line], [$fundSheet, 4, 'H', $fundTotalLine]] as [$reportSheet, $header, $lastColumn, $lastRow]) {
            $reportSheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)
                ->setRowsToRepeatAtTopByStartAndEnd($header, $header)->setPrintArea('A1:'.$lastColumn.$lastRow);
            $reportSheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);
            $reportSheet->getHeaderFooter()->setOddFooter('&LPermata Insani&RHalaman &P / &N');
        }
        $spreadsheet->setActiveSheetIndex(0);

        return $this->download($spreadsheet, 'laporan-semua-transaksi-'.now()->format('Ymd-His').'.xlsx');
    }

    private function transactionIndex(Request $request)
    {
        $selected = $request->string('jenis_transaksi')->toString();
        $queries = [];
        if ($selected === '' || $selected === 'tagihan') {
            $query = Pembayaran::query();
            $this->filterPaymentQuery($query, $request);
            $key = \App\Support\ServerTable::paymentKey();
            $groups = $query->selectRaw($key.' AS group_key, MIN(pembayaran.id) AS first_id, MAX(pembayaran.id) AS sort_id, SUM(jumlah_bayar + COALESCE(diskon, 0)) AS gross, SUM(COALESCE(diskon, 0)) AS discount, SUM(jumlah_bayar) AS received')->groupBy('group_key');
            $this->indexFunds($groups, 'jumlah_bayar', 'metode_bayar', true);
            $paymentIndex = \Illuminate\Support\Facades\DB::query()->fromSub($groups, 'g')
                ->join('pembayaran AS first_payment', 'first_payment.id', '=', 'g.first_id')->leftJoin('siswa AS party', 'party.id', '=', 'first_payment.siswa_id')
                ->selectRaw("'tagihan' AS source, g.group_key AS row_key, g.sort_id, first_payment.tanggal_bayar AS date, COALESCE(NULLIF(first_payment.nomor_kwitansi, ''), NULLIF(first_payment.transaction_id, ''), ".$this->textId('Pembayaran #', 'first_payment.id').") AS reference, COALESCE(party.nama, '-') AS party, g.gross, g.discount, g.received, g.fund_tunai, g.fund_transfer, g.fund_kjc, g.fund_tabungan, g.fund_belum");
            $queries[] = $paymentIndex;
        }
        if ($selected === '' || $selected === 'koperasi') {
            $query = KoperasiPenjualan::query();
            $this->filterCooperativeQuery($query, $request);
            $query->leftJoin('siswa AS party', 'party.id', '=', 'koperasi_penjualan.siswa_id')
                ->selectRaw("'koperasi' AS source, koperasi_penjualan.id AS row_key, koperasi_penjualan.id AS sort_id, koperasi_penjualan.tanggal AS date, COALESCE(NULLIF(kode_transaksi, ''), ".$this->textId('Koperasi #', 'koperasi_penjualan.id').") AS reference, COALESCE(party.nama, 'Pembeli umum') AS party, total AS gross, 0 AS discount, total AS received");
            $this->indexFunds($query, 'total', 'metode_bayar', false);
            $queries[] = $query->toBase();
        }
        if ($selected === '' || $selected === 'pemasukan') {
            $query = Pemasukan::query();
            $this->filterIncomeQuery($query, $request);
            $reference = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' ? "printf('PM-%06d', pemasukan.id)" : "CONCAT('PM-', LPAD(pemasukan.id, 6, '0'))";
            $query->selectRaw("'pemasukan' AS source, id AS row_key, id AS sort_id, tanggal AS date, {$reference} AS reference, sumber AS party, jumlah AS gross, 0 AS discount, jumlah AS received, 0 AS fund_tunai, 0 AS fund_transfer, 0 AS fund_kjc, 0 AS fund_tabungan, jumlah AS fund_belum");
            $queries[] = $query->toBase();
        }
        if (!$queries) return $this->transactionIndex(Request::create($request->url(), 'GET', array_merge($request->query(), ['jenis_transaksi' => 'tagihan'])))->whereRaw('1 = 0');
        $union = array_shift($queries);
        foreach ($queries as $query) $union->unionAll($query);
        return \Illuminate\Support\Facades\DB::query()->fromSub($union, 'transactions');
    }

    private function textId(string $prefix, string $column): string
    {
        return \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' ? "'{$prefix}' || {$column}" : "CONCAT('{$prefix}', {$column})";
    }

    private function indexFunds($query, string $amount, string $column, bool $aggregate): void
    {
        foreach (['tunai', 'transfer', 'kjc', 'tabungan', 'belum'] as $method) {
            $condition = $method === 'belum' ? "{$column} IS NULL OR LOWER(TRIM({$column})) NOT IN ('tunai', 'transfer', 'kjc', 'tabungan')" : "LOWER(TRIM({$column})) = '{$method}'";
            $expression = "CASE WHEN {$condition} THEN {$amount} ELSE 0 END";
            $query->selectRaw(($aggregate ? "SUM({$expression})" : $expression)." AS fund_{$method}");
        }
    }

    private function transactions(Request $request, ?Collection $keys = null): Collection
    {
        $selectedType = $request->string('jenis_transaksi')->toString();
        $rows = collect();

        if (($selectedType === '' || $selectedType === 'tagihan') && ($keys === null || $keys->contains('source', 'tagihan'))) {
            $query = Pembayaran::with(['siswa.kelas', 'siswa.sekolah', 'tagihan.jenisPembayaran.tahunAjaran', 'tagihan.tahunAjaran']);
            $this->filterPaymentQuery($query, $request);
            if ($keys !== null) $query->whereIn(\Illuminate\Support\Facades\DB::raw(\App\Support\ServerTable::paymentKey()), $keys->where('source', 'tagihan')->pluck('row_key'));

            $paymentRows = $query->get()
                ->groupBy(fn ($payment) => $payment->transaction_id ?: ($payment->nomor_kwitansi ?: 'payment-'.$payment->id))
                ->map(function (Collection $payments) {
                    $first = $payments->sortBy('id')->first();
                    $descriptions = $payments->map(function ($payment) {
                        $period = $payment->periode ?: $payment->tagihan?->periode;
                        return app(\App\Services\BillDisplayName::class)->report($payment->tagihan, $period);
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
                        'funds' => $this->funds($payments),
                        'method' => $payments->pluck('metode_bayar')->filter()->map(fn ($method) => ucfirst($method))->unique()->implode(', ') ?: '-',
                        'note' => $payments->pluck('keterangan')->filter()->unique()->implode('; '),
                    ];
                })->values();

            $rows = $rows->concat($paymentRows);
        }

        if (($selectedType === '' || $selectedType === 'koperasi') && ($keys === null || $keys->contains('source', 'koperasi'))) {
            $query = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'siswa.sekolah', 'details']);
            $this->filterCooperativeQuery($query, $request);
            if ($keys !== null) $query->whereIn('koperasi_penjualan.id', $keys->where('source', 'koperasi')->pluck('row_key'));

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
                    'funds' => $this->funds([(object) ['metode_bayar' => $sale->metode_bayar, 'jumlah_bayar' => $sale->total]]),
                    'method' => match ($sale->metode_bayar) { 'tunai' => 'Tunai (TN)', 'transfer' => 'Transfer (TF)', default => 'Belum dicatat' },
                    'note' => (string) ($sale->catatan ?? ''),
                ];
            }));
        }

        if (($selectedType === '' || $selectedType === 'pemasukan') && ($keys === null || $keys->contains('source', 'pemasukan'))) {
            $query = Pemasukan::with('sekolah');
            $this->filterIncomeQuery($query, $request);
            if ($keys !== null) $query->whereIn('pemasukan.id', $keys->where('source', 'pemasukan')->pluck('row_key'));

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
                    'funds' => $this->funds([(object) ['metode_bayar' => null, 'jumlah_bayar' => $income->jumlah]]),
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
            'funds' => collect(['tunai', 'transfer', 'kjc', 'tabungan', 'belum'])->mapWithKeys(fn ($method) => [$method => (float) $rows->sum(fn ($row) => $row['funds'][$method])])->all(),
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
