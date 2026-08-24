<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sekolah;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\LogAktivitas;
use App\Models\KoperasiPenjualan;
use App\Services\SppDiscountCalculator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PembayaranController extends Controller
{
    public function __construct(private readonly SppDiscountCalculator $sppDiscountCalculator)
    {
    }

    public function index(Request $request)
    {
        $filterRules = [
            'sekolah_id' => ['nullable', 'integer', 'exists:sekolah,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'jenis_pembayaran' => ['nullable', 'in:sekolah,koperasi'],
            'search' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];

        if ($request->filled('start_date')) {
            $filterRules['end_date'][] = 'after_or_equal:start_date';
        }

        $filters = $request->validate($filterRules);

        $selectedSekolah = isset($filters['sekolah_id']) ? (int) $filters['sekolah_id'] : null;
        $selectedKelas = isset($filters['kelas_id']) ? (int) $filters['kelas_id'] : null;
        $selectedJenisPembayaran = $filters['jenis_pembayaran'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($selectedSekolah && $selectedKelas) {
            $classBelongsToSchool = Kelas::query()
                ->whereKey($selectedKelas)
                ->where('sekolah_id', $selectedSekolah)
                ->exists();

            if (!$classBelongsToSchool) {
                $selectedKelas = null;
            }
        }

        $sekolahList = Sekolah::query()
            ->orderBy('nama_sekolah')
            ->get();

        $kelasList = Kelas::query()
            ->with('sekolah')
            ->when($selectedSekolah, fn ($query) => $query->where('sekolah_id', $selectedSekolah))
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        return $this->indexWithKoperasi(
            $request,
            $sekolahList,
            $kelasList,
            $selectedSekolah,
            $selectedKelas,
            $selectedJenisPembayaran,
            $search,
            $startDate,
            $endDate
        );
    }

    private function indexWithKoperasi(
        Request $request,
        $sekolahList,
        $kelasList,
        $selectedSekolah,
        $selectedKelas,
        $selectedJenisPembayaran,
        $search,
        $startDate,
        $endDate
    ) {
        $transaksi = collect();

        if ($selectedJenisPembayaran !== 'koperasi') {
            $query = Pembayaran::with(['siswa.kelas', 'siswa.sekolah', 'tagihan'])
                ->latest('tanggal_bayar')
                ->latest('id');

            if ($selectedSekolah) {
                $query->whereHas('siswa', function ($q) use ($selectedSekolah) {
                    $q->where('id_sekolah', $selectedSekolah);
                });
            }

            if ($selectedKelas) {
                $query->whereHas('siswa', function ($q) use ($selectedKelas) {
                    $q->where('kelas_id', $selectedKelas);
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    })
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhere('nomor_kwitansi', 'like', "%{$search}%")
                        ->orWhere('metode_bayar', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%")
                        ->orWhereHas('tagihan', function ($tagihanQuery) use ($search) {
                            $tagihanQuery->where('nama_tagihan', 'like', "%{$search}%")
                                ->orWhere('periode', 'like', "%{$search}%");
                        });
                });
            }

            $query
                ->when($startDate, fn ($paymentQuery) => $paymentQuery->whereDate('tanggal_bayar', '>=', $startDate))
                ->when($endDate, fn ($paymentQuery) => $paymentQuery->whereDate('tanggal_bayar', '<=', $endDate));

            $transaksi = $transaksi->merge($query->get()->groupBy(function ($item) {
                if (!empty($item->transaction_id)) {
                    return $item->transaction_id;
                }

                return $item->created_at->toDateTimeString() . '-' . $item->siswa_id;
            })->map(function ($group) {
                return [
                    'source_type' => 'sekolah',
                    'source_label' => 'Pembayaran Sekolah',
                    'items' => $group,
                    'total_bayar' => $group->sum('jumlah_bayar'),
                    'total_diskon' => $group->sum(function ($item) { return $item->diskon ?? 0; }),
                    'siswa' => $group->first()->siswa,
                    'tanggal_bayar' => $group->first()->tanggal_bayar,
                    'tanggal_kwitansi' => $group->first()->tanggal_kwitansi ?? $group->first()->tanggal_bayar,
                    'metode_bayar' => $group->first()->metode_bayar,
                    'keterangan' => $group->first()->keterangan,
                    'ids' => $group->pluck('id')->implode(','),
                    'transaction_id' => $group->first()->transaction_id,
                ];
            })->values());
        }

        if ($selectedJenisPembayaran !== 'sekolah') {
            $koperasiQuery = KoperasiPenjualan::with(['siswa.kelas', 'siswa.sekolah', 'details'])
                ->latest('tanggal')
                ->latest('id');

            if ($selectedSekolah) {
                $koperasiQuery->whereHas('siswa', function ($q) use ($selectedSekolah) {
                    $q->where('id_sekolah', $selectedSekolah);
                });
            }

            if ($selectedKelas) {
                $koperasiQuery->whereHas('siswa', function ($q) use ($selectedKelas) {
                    $q->where('kelas_id', $selectedKelas);
                });
            }

            if ($search) {
                $koperasiQuery->where(function ($q) use ($search) {
                    $q->where('kode_transaksi', 'like', "%{$search}%")
                        ->orWhere('catatan', 'like', "%{$search}%")
                        ->orWhereHas('siswa', function ($sq) use ($search) {
                            $sq->where('nama', 'like', "%{$search}%")
                                ->orWhere('nis', 'like', "%{$search}%");
                        })
                        ->orWhereHas('details', function ($dq) use ($search) {
                            $dq->where('nama_barang', 'like', "%{$search}%");
                        });
                });
            }

            $koperasiQuery
                ->when($startDate, fn ($salesQuery) => $salesQuery->whereDate('tanggal', '>=', $startDate))
                ->when($endDate, fn ($salesQuery) => $salesQuery->whereDate('tanggal', '<=', $endDate));

            $transaksi = $transaksi->merge($koperasiQuery->get()->map(function ($penjualan) {
                return [
                    'source_type' => 'koperasi',
                    'source_label' => 'Koperasi',
                    'items' => $penjualan->details,
                    'total_bayar' => $penjualan->total,
                    'total_diskon' => 0,
                    'siswa' => $penjualan->siswa,
                    'tanggal_bayar' => $penjualan->tanggal,
                    'metode_bayar' => 'Koperasi',
                    'keterangan' => $penjualan->catatan,
                    'ids' => (string) $penjualan->id,
                    'transaction_id' => $penjualan->kode_transaksi,
                ];
            }));
        }

        $transaksi = $transaksi
            ->sortByDesc(fn ($item) => Carbon::parse($item['tanggal_bayar']))
            ->values();

        $transactionSummary = [
            'total' => $transaksi->count(),
            'sekolah' => $transaksi->where('source_type', 'sekolah')->count(),
            'koperasi' => $transaksi->where('source_type', 'koperasi')->count(),
            'nominal' => (float) $transaksi->sum('total_bayar'),
        ];

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $transaksi = new LengthAwarePaginator(
            $transaksi->forPage($currentPage, $perPage)->values(),
            $transactionSummary['total'],
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::id(),
            'aktivitas'  => 'Melihat halaman riwayat pembayaran',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('riwayat.index', compact(
            'transaksi',
            'sekolahList',
            'kelasList',
            'selectedSekolah',
            'selectedKelas',
            'selectedJenisPembayaran',
            'search',
            'startDate',
            'endDate',
            'transactionSummary'
        ));
    }

    public function riwayat(Request $request)
    {
        // Get all schools for dropdown
        $sekolah = Sekolah::all();
        $selectedSekolah = $request->input('sekolah_id');
        $selectedKelas = $request->input('kelas_id');
        $search = $request->input('search');

        // Get classes if school is selected
        $kelas = $selectedSekolah ? Kelas::where('sekolah_id', $selectedSekolah)->get() : collect();

        // Build the query with relationships
        $query = Pembayaran::with([
            'siswa.kelas',
            'siswa.sekolah',
            'tagihan.jenisPembayaran'
        ])->orderBy('tanggal_bayar', 'desc');

        // Apply school filter
        if ($selectedSekolah) {
            $query->whereHas('siswa', function ($q) use ($selectedSekolah) {
                $q->where('id_sekolah', $selectedSekolah);
            });
        }

        // Apply class filter
        if ($selectedKelas) {
            $query->whereHas('siswa', function ($q) use ($selectedKelas) {
                $q->where('kelas_id', $selectedKelas);
            });
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orWhere('jumlah_bayar', 'like', "%{$search}%");
            });
        }

        // Execute query
        $riwayat = $query->get();

        // Log activity
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::id(),
            'aktivitas'  => 'Membuka halaman riwayat pembayaran lengkap',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Return view with all necessary data
        return view('riwayat.index', compact(
            'riwayat',
            'sekolah',
            'kelas',
            'selectedSekolah',
            'selectedKelas',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'      => 'required|exists:siswa,id',
            'tagihan_id'    => 'required|exists:tagihan,id',
            'jumlah_bayar'  => 'required|numeric|min:1',
            'tanggal_bayar' => 'required|date',
            'tanggal_kwitansi' => 'nullable|date',
            'metode_bayar'  => 'nullable|in:tunai,transfer,kjc,tabungan',
            'keterangan'    => 'nullable|string',
            'bukti_bayar'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $tagihan = Tagihan::query()
                ->with('siswa')
                ->lockForUpdate()
                ->findOrFail($validated['tagihan_id']);

            if ((int) $tagihan->siswa_id !== (int) $validated['siswa_id']) {
                throw ValidationException::withMessages([
                    'siswa_id' => 'Tagihan yang dipilih bukan milik siswa tersebut.',
                ]);
            }

            $totalCoverage = (float) Pembayaran::query()
                ->where('tagihan_id', $tagihan->id)
                ->selectRaw('COALESCE(SUM(jumlah_bayar + COALESCE(diskon, 0)), 0) AS total_coverage')
                ->value('total_coverage');
            $sisa = max(0, (float) $tagihan->nominal - $totalCoverage);
            $jumlahBayar = (float) $validated['jumlah_bayar'];

            if ($sisa <= 0) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Tagihan tersebut sudah lunas.',
                ]);
            }

            if ($jumlahBayar > ($sisa + 0.01)) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah pembayaran melebihi sisa tagihan sebesar Rp ' . number_format($sisa, 0, ',', '.') . '.',
                ]);
            }

            $existingCicilan = Pembayaran::where('tagihan_id', $tagihan->id)->count();
            $cicilanKe = $existingCicilan + 1;
            $buktiPath = $request->hasFile('bukti_bayar')
                ? $request->file('bukti_bayar')->store('bukti_bayar', 'public')
                : null;

            $pembayaran = Pembayaran::create([
                'sekolah_id'     => $tagihan->siswa->id_sekolah,
                'tagihan_id'     => $tagihan->id,
                'siswa_id'       => $tagihan->siswa_id,
                'jumlah_bayar'   => $jumlahBayar,
                'tanggal_bayar'  => $validated['tanggal_bayar'],
                'tanggal_kwitansi' => $validated['tanggal_kwitansi'] ?? $validated['tanggal_bayar'],
                'periode'        => $tagihan->periode,
                'periode_tahun'  => $tagihan->periode_tahun,
                'metode_bayar'   => $validated['metode_bayar'] ?? null,
                'cicilan_ke'     => $cicilanKe,
                'total_cicilan'  => $cicilanKe,
                'keterangan'     => $validated['keterangan'] ?? null,
                'bukti_bayar'    => $buktiPath,
            ]);

            $tagihan->update([
                'status' => ($jumlahBayar + $totalCoverage) >= (float) $tagihan->nominal ? 'lunas' : 'belum',
            ]);

            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id'   => Auth::id(),
                'aktivitas'  => 'Menambahkan pembayaran untuk siswa: ' . $pembayaran->siswa->nama,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->back()->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    /**
     * Tampilkan satu transaksi pembayaran yang akan dikoreksi.
     * Pembayaran multi-tagihan selalu dikoreksi sebagai satu transaksi agar
     * metode, tanggal, siswa, dan nomor kuitansinya tetap konsisten.
     */
    public function editCorrection(int $id)
    {
        $anchor = Pembayaran::findOrFail($id);
        $payments = $this->correctionGroupQuery($anchor)
            ->with(['siswa.kelas', 'siswa.sekolah', 'tagihan.jenisPembayaran', 'tagihan.tahunAjaran'])
            ->orderBy('id')
            ->get();

        $students = Siswa::query()
            ->with(['sekolah', 'kelas'])
            ->orderBy('id_sekolah')
            ->orderBy('nama')
            ->get();

        return view('riwayat.koreksi', [
            'anchor' => $anchor,
            'payments' => $payments,
            'students' => $students,
            'currentStudent' => $payments->first()?->siswa,
            'transactionReference' => $anchor->transaction_id ?: ($anchor->nomor_kwitansi ?: '#' . $anchor->id),
        ]);
    }

    /**
     * Koreksi transaksi tanpa menghapus riwayat pembayaran.
     * Jika siswa diganti, pembayaran dipindahkan ke tagihan pasangan milik
     * siswa tujuan (jenis, tahun ajaran, tipe, dan periode harus sama).
     */
    public function updateCorrection(Request $request, int $id)
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
            'tanggal_bayar' => ['required', 'date'],
            'tanggal_kwitansi' => ['required', 'date'],
            'metode_bayar' => ['required', 'in:tunai,transfer,kjc,tabungan'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'alasan_koreksi' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan_koreksi.required' => 'Alasan koreksi wajib ditulis untuk jejak perubahan transaksi.',
            'alasan_koreksi.min' => 'Alasan koreksi harus menjelaskan perubahan yang dilakukan.',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $anchor = Pembayaran::query()->lockForUpdate()->findOrFail($id);
            $payments = $this->correctionGroupQuery($anchor)
                ->with(['siswa.sekolah', 'tagihan.jenisPembayaran', 'tagihan.tahunAjaran'])
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi pembayaran tidak ditemukan.',
                ]);
            }

            $sourceStudentIds = $payments->pluck('siswa_id')->unique();
            if ($sourceStudentIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'siswa_id' => 'Transaksi lama berisi lebih dari satu siswa dan tidak dapat dikoreksi otomatis.',
                ]);
            }

            $sourceStudent = $payments->first()->siswa;
            $targetStudent = Siswa::with('sekolah')->lockForUpdate()->findOrFail((int) $validated['siswa_id']);
            $isChangingStudent = (int) $sourceStudentIds->first() !== (int) $targetStudent->id;
            $billAssignments = [];
            $affectedBillIds = $payments->pluck('tagihan_id')->filter()->map(fn ($value) => (int) $value)->all();

            if ($isChangingStudent) {
                foreach ($payments as $payment) {
                    $sourceBill = Tagihan::query()->lockForUpdate()->find($payment->tagihan_id);

                    if (!$sourceBill) {
                        throw ValidationException::withMessages([
                            'siswa_id' => 'Salah satu tagihan asal sudah tidak tersedia. Siswa transaksi tidak dapat dipindahkan otomatis.',
                        ]);
                    }

                    $targetBillQuery = Tagihan::query()
                        ->where('siswa_id', $targetStudent->id)
                        ->where('jenis_pembayaran_id', $sourceBill->jenis_pembayaran_id)
                        ->where('tahun_ajaran_id', $sourceBill->tahun_ajaran_id)
                        ->where('tipe', $sourceBill->tipe)
                        ->where('periode', $sourceBill->periode);

                    if (!$sourceBill->jenis_pembayaran_id) {
                        $targetBillQuery->where('nama_tagihan', $sourceBill->nama_tagihan);
                    }

                    $targetBill = $targetBillQuery->lockForUpdate()->first();

                    if (!$targetBill) {
                        throw ValidationException::withMessages([
                            'siswa_id' => 'Tagihan pasangan "' . $this->receiptBillName($sourceBill) . '" tidak ditemukan pada siswa tujuan. Buat tagihan siswa tujuan terlebih dahulu.',
                        ]);
                    }

                    $billAssignments[$payment->id] = $targetBill;
                    $affectedBillIds[] = $targetBill->id;
                }
            }

            $paymentIds = $payments->pluck('id')->map(fn ($value) => (int) $value)->all();
            $coverageByBill = [];
            $paymentAdjustments = [];

            foreach ($payments as $payment) {
                $targetBill = $billAssignments[$payment->id] ?? $payment->tagihan;

                if (! $targetBill) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Salah satu tagihan transaksi sudah tidak tersedia.',
                    ]);
                }

                if (! array_key_exists($targetBill->id, $coverageByBill)) {
                    $coverageByBill[$targetBill->id] = (float) Pembayaran::query()
                        ->where('tagihan_id', $targetBill->id)
                        ->whereNotIn('id', $paymentIds)
                        ->selectRaw('COALESCE(SUM(jumlah_bayar + COALESCE(diskon, 0)), 0) as total')
                        ->value('total');
                }

                $discount = $this->sppDiscountCalculator->settlementDiscount(
                    $targetBill,
                    $validated['tanggal_bayar'],
                    (float) $payment->jumlah_bayar,
                    $coverageByBill[$targetBill->id]
                );
                $coverageByBill[$targetBill->id] += (float) $payment->jumlah_bayar + $discount;

                if ($coverageByBill[$targetBill->id] > ((float) $targetBill->nominal + 0.01)) {
                    throw ValidationException::withMessages([
                        'siswa_id' => 'Pembayaran tidak dapat dikoreksi karena tagihan "' . $this->receiptBillName($targetBill) . '" akan melebihi nominal.',
                    ]);
                }

                $paymentAdjustments[$payment->id] = [
                    'bill' => $targetBill,
                    'discount' => $discount,
                ];
            }

            $oldDescription = $this->paymentCorrectionDescription($payments);
            $newReceiptNumber = null;

            if ($isChangingStudent && (int) $sourceStudent?->id_sekolah !== (int) $targetStudent->id_sekolah) {
                $newReceiptNumber = $targetStudent->sekolah?->getNextNomorKwitansi();
            }

            foreach ($payments as $payment) {
                $targetBill = $paymentAdjustments[$payment->id]['bill'];

                $payment->forceFill([
                    'siswa_id' => $targetStudent->id,
                    'sekolah_id' => $targetStudent->id_sekolah,
                    'tagihan_id' => $targetBill?->id,
                    'periode' => $targetBill?->periode ?? $payment->periode,
                    'tanggal_bayar' => $validated['tanggal_bayar'],
                    'tanggal_kwitansi' => $validated['tanggal_kwitansi'],
                    'diskon' => $paymentAdjustments[$payment->id]['discount'],
                    'metode_bayar' => $validated['metode_bayar'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'nomor_kwitansi' => $newReceiptNumber ?: $payment->nomor_kwitansi,
                ])->save();
            }

            Tagihan::query()
                ->whereIn('id', array_values(array_unique($affectedBillIds)))
                ->get()
                ->each(fn (Tagihan $tagihan) => $this->syncBillPaymentStatus($tagihan));

            $payments = $this->correctionGroupQuery($anchor->fresh())
                ->with(['siswa.sekolah', 'tagihan'])
                ->orderBy('id')
                ->get();
            $newDescription = $this->paymentCorrectionDescription($payments);
            $reference = $anchor->transaction_id ?: ($payments->first()?->nomor_kwitansi ?: '#' . $anchor->id);

            return compact('sourceStudent', 'targetStudent', 'oldDescription', 'newDescription', 'reference');
        });

        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::id(),
            'aktivitas' => 'Koreksi transaksi ' . $result['reference']
                . '. Alasan: ' . $validated['alasan_koreksi']
                . '. Sebelum: ' . $result['oldDescription']
                . '. Sesudah: ' . $result['newDescription'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('riwayat.index')
            ->with('success', 'Transaksi berhasil dikoreksi. Saldo tagihan dan kuitansi telah diperbarui.');
    }

    // Tambahan opsional bila ingin menghitung jumlah uang masuk dari cicilan
    private function hitungTotalCicilan($tagihan_id)
    {
        return Pembayaran::where('tagihan_id', $tagihan_id)->sum('jumlah_bayar');
    }

    public function cetakKwitansi($id)
    {
        return view('tagihan.kwitansi.receipt', [
            'receipt' => $this->receiptForPayment((int) $id),
        ]);
    }

    public function unduhKwitansiPdf(Request $request, $id)
    {
        return $this->downloadReceiptPdf(
            $this->receiptForPayment((int) $id),
            $request->boolean('preview')
        );
    }

    private function receiptForPayment(int $id): array
    {
        $pembayaran = Pembayaran::with([
            'siswa' => function($query) {
                $query->with(['kelas', 'sekolah']);
            },
            'tagihan' => function($query) {
                $query->with(['jenisPembayaran.tahunAjaran', 'tahunAjaran']);
            }
        ])->findOrFail($id);

        $tagihan = $pembayaran->tagihan;
        $billName = $this->receiptBillName($tagihan);

        $receipt = [
            'student' => $pembayaran->siswa,
            'number' => $pembayaran->nomor_kwitansi ?? str_pad((string) $pembayaran->id, 6, '0', STR_PAD_LEFT),
            'payment_date' => $pembayaran->tanggal_bayar,
            'receipt_date' => $pembayaran->tanggal_kwitansi ?? $pembayaran->tanggal_bayar,
            'method' => $this->paymentMethodLabel($pembayaran->metode_bayar),
            'note' => $pembayaran->keterangan,
            'items' => [[
                'name' => $billName,
                'amount' => (float) $pembayaran->jumlah_bayar,
                'discount' => (float) $pembayaran->diskon,
            ]],
            'total_paid' => (float) $pembayaran->jumlah_bayar,
            'total_discount' => (float) $pembayaran->diskon,
            'pdf_url' => route('pembayaran.kwitansi.pdf', ['id' => $pembayaran->id]),
        ];

        return $receipt;
    }

    public function cetakKwitansiGrup(Request $request)
    {
        $ids = array_values(array_filter(explode(',', (string) $request->query('ids'))));

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data pembayaran yang dipilih.');
        }

        return view('tagihan.kwitansi.receipt', [
            'receipt' => $this->receiptForPaymentGroup($ids),
        ]);
    }

    public function unduhKwitansiGrupPdf(Request $request)
    {
        $ids = array_values(array_filter(explode(',', (string) $request->query('ids'))));

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data pembayaran yang dipilih.');
        }

        return $this->downloadReceiptPdf(
            $this->receiptForPaymentGroup($ids),
            $request->boolean('preview')
        );
    }

    private function receiptForPaymentGroup(array $ids): array
    {
        $pembayaranItems = Pembayaran::with(['siswa.kelas', 'siswa.sekolah', 'tagihan.jenisPembayaran.tahunAjaran', 'tagihan.tahunAjaran'])
            ->whereIn('id', $ids)
            ->get();

        if ($pembayaranItems->isEmpty()) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        $firstItem = $pembayaranItems->first();
        $receipt = [
            'student' => $firstItem->siswa,
            'number' => $firstItem->nomor_kwitansi ?? str_pad((string) $firstItem->id, 6, '0', STR_PAD_LEFT),
            'payment_date' => $firstItem->tanggal_bayar,
            'receipt_date' => $firstItem->tanggal_kwitansi ?? $firstItem->tanggal_bayar,
            'method' => $this->paymentMethodLabel($firstItem->metode_bayar),
            'note' => $firstItem->keterangan,
            'items' => $pembayaranItems->map(function (Pembayaran $item) {
                return [
                    'name' => $this->receiptBillName($item->tagihan),
                    'amount' => (float) $item->jumlah_bayar,
                    'discount' => (float) $item->diskon,
                ];
            })->all(),
            'total_paid' => (float) $pembayaranItems->sum('jumlah_bayar'),
            'total_discount' => (float) $pembayaranItems->sum('diskon'),
            'pdf_url' => route('pembayaran.kwitansi.grup.pdf', ['ids' => implode(',', $pembayaranItems->pluck('id')->all())]),
        ];

        return $receipt;
    }

    private function downloadReceiptPdf(array $receipt, bool $preview = false)
    {
        $safeNumber = Str::of((string) $receipt['number'])
            ->replace(['/', '\\'], '-')
            ->replaceMatches('/[^A-Za-z0-9._-]/', '-')
            ->trim('-')
            ->value();

        $filename = 'kwitansi-' . ($safeNumber ?: 'pembayaran') . '.pdf';

        if (! class_exists(\Barryvdh\DomPDF\PDF::class) || ! app()->bound('dompdf.wrapper')) {
            return $this->clientReceiptPdfFallback($receipt, $preview);
        }

        try {
            $pdf = app('dompdf.wrapper')
                ->loadView('tagihan.kwitansi.pdf', compact('receipt'))
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->clientReceiptPdfFallback($receipt, $preview);
        }

        return $preview
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    private function clientReceiptPdfFallback(array $receipt, bool $preview)
    {
        return view('tagihan.kwitansi.receipt', [
            'receipt' => $receipt,
            'clientPdfFallback' => ! $preview,
            'clientPrintFallback' => $preview,
        ]);
    }

    private function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'tunai' => 'Tunai',
            'transfer' => 'Transfer bank',
            'kjc' => 'KJC',
            'tabungan' => 'Potongan tabungan',
            default => ucfirst((string) $method) ?: '-',
        };
    }

    private function correctionGroupQuery(Pembayaran $anchor)
    {
        return Pembayaran::query()->when(
            filled($anchor->transaction_id),
            fn ($query) => $query->where('transaction_id', $anchor->transaction_id),
            fn ($query) => $query->whereKey($anchor->id)
        );
    }

    private function syncBillPaymentStatus(Tagihan $tagihan): void
    {
        $applied = (float) $tagihan->pembayaran()
            ->selectRaw('COALESCE(SUM(jumlah_bayar + COALESCE(diskon, 0)), 0) as total')
            ->value('total');

        $tagihan->update([
            'status' => $applied + 0.01 >= (float) $tagihan->nominal ? 'lunas' : 'belum',
        ]);
    }

    private function paymentCorrectionDescription($payments): string
    {
        $first = $payments->first();

        if (!$first) {
            return 'data tidak tersedia';
        }

        return implode(', ', [
            'siswa ' . ($first->siswa?->nama ?? '-'),
            'metode ' . $this->paymentMethodLabel($first->metode_bayar),
            'tanggal bayar ' . optional($first->tanggal_bayar)->format('d-m-Y'),
            'tanggal kuitansi ' . optional($first->tanggal_kwitansi ?? $first->tanggal_bayar)->format('d-m-Y'),
            'potongan Rp ' . number_format((float) $payments->sum('diskon'), 0, ',', '.'),
            $payments->count() . ' tagihan',
        ]);
    }

    /**
     * Nama ringkas khusus kuitansi:
     * - SPP ditulis per bulan (contoh: SPP AGUSTUS)
     * - Activity School hanya memakai nama umum dan tahun ajaran.
     */
    private function receiptBillName(?Tagihan $tagihan): string
    {
        if (!$tagihan) {
            return 'Tagihan dihapus';
        }

        $typeName = trim((string) ($tagihan->jenisPembayaran?->nama_pembayaran ?: $tagihan->nama_tagihan));

        if (preg_match('/\bspp\b/i', $typeName)) {
            $period = trim((string) $tagihan->periode);

            if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
                $monthName = Carbon::create((int) $matches[1], (int) $matches[2], 1)
                    ->locale('id')
                    ->translatedFormat('F');

                return 'SPP ' . Str::upper($monthName);
            }

            return 'SPP';
        }

        if (Str::contains(Str::lower($typeName), 'activity school')) {
            $academicYear = $tagihan->tahunAjaran?->label
                ?? $tagihan->jenisPembayaran?->tahunAjaran?->label;

            return $academicYear
                ? 'Activity School - Tahun Ajaran ' . $academicYear
                : 'Activity School';
        }

        return convertBulanToIndonesia($tagihan->nama_tagihan);
    }
}
