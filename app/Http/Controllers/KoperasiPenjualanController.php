<?php

namespace App\Http\Controllers;

use App\Models\Koperasi;
use App\Models\KoperasiPenjualan;
use App\Models\LogAktivitas;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KoperasiPenjualanController extends Controller
{
    public function index(Request $request)
    {
        $sekolah = Sekolah::orderBy('nama_sekolah')->get();
        $selectedSekolah = $request->input('sekolah_id');
        $search = $request->input('search');

        $query = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'details'])
            ->latest('tanggal')
            ->latest('id');

        if (!empty($selectedSekolah)) {
            $query->where('sekolah_id', $selectedSekolah);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('siswa', function ($siswaQuery) use ($search) {
                        $siswaQuery->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
            });
        }

        $penjualan = $query->paginate(10)->appends($request->query());

        return view('koperasi.penjualan.index', compact(
            'penjualan',
            'sekolah',
            'selectedSekolah',
            'search'
        ));
    }

    public function create()
    {
        $siswa = Siswa::with(['sekolah', 'kelas'])
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get();

        $barang = Koperasi::with('sekolah')
            ->where('status', 'aktif')
            ->where('jenis_transaksi', 'jual')
            ->orderBy('nama_barang')
            ->get();

        return view('koperasi.penjualan.create', compact('siswa', 'barang'));
    }

    public function edit(int $id)
    {
        $penjualan = KoperasiPenjualan::with('details')->findOrFail($id);
        $siswa = Siswa::with(['sekolah', 'kelas'])->where('status', 'aktif')->orWhere('id', $penjualan->siswa_id)->orderBy('nama')->get();
        $barang = Koperasi::with('sekolah')->where(function ($query) use ($penjualan) {
            $query->where(function ($q) { $q->where('status', 'aktif')->where('jenis_transaksi', 'jual'); })
                ->orWhereIn('id', $penjualan->details->pluck('koperasi_id'));
        })->orderBy('nama_barang')->get();
        foreach ($barang as $item) {
            $old = $penjualan->details->where('koperasi_id', $item->id);
            $item->stok += $old->sum('jumlah');
            if ($old->isNotEmpty()) { $item->harga_jual = $old->first()->harga_satuan; }
        }
        return view('koperasi.penjualan.create', compact('siswa', 'barang', 'penjualan'));
    }

    public function update(Request $request, int $id)
    {
        return $this->saveSale($request, $id);
    }

    private function saveSale(Request $request, ?int $id = null)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id', 'tanggal' => 'required|date',
            'catatan' => 'nullable|string|max:1000', 'metode_bayar' => 'required|in:tunai,transfer,dompet',
            'alasan_koreksi' => $id ? 'required|string|min:5|max:1000' : 'nullable|string|max:1000',
            'items' => 'required|array', 'items.*.koperasi_id' => 'nullable|exists:koperasi,id',
            'items.*.jumlah' => 'nullable|integer|min:1',
        ]);
        $items = collect($validated['items'])->filter(fn ($item) => !empty($item['koperasi_id']) && !empty($item['jumlah']))
            ->groupBy('koperasi_id')->map(fn ($group, $key) => ['koperasi_id' => (int) $key, 'jumlah' => $group->sum('jumlah')])->values();
        if ($items->isEmpty()) { throw ValidationException::withMessages(['items' => 'Pilih minimal satu barang yang dibeli.']); }

        $sale = DB::transaction(function () use ($id, $validated, $items, $request) {
            $sale = $id ? KoperasiPenjualan::lockForUpdate()->findOrFail($id) : new KoperasiPenjualan;
            if ($id && $sale->metode_bayar === 'dompet') {
                throw ValidationException::withMessages([
                    'metode_bayar' => 'Transaksi yang sudah dibayar melalui dompet belum dapat dikoreksi. Batalkan transaksi lalu buat transaksi baru.',
                ]);
            }
            $old = $id ? $sale->details()->get() : collect();
            $before = $id ? ['transaksi' => $sale->toArray(), 'items' => $old->toArray()] : null;
            $goods = Koperasi::whereIn('id', $items->pluck('koperasi_id')->merge($old->pluck('koperasi_id'))->unique())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($old as $detail) {
                if ($good = $goods->get($detail->koperasi_id)) { $good->increment('stok', $detail->jumlah); }
            }
            $student = Siswa::findOrFail($validated['siswa_id']);
            $prepared = collect();
            foreach ($items as $item) {
                $good = $goods->get($item['koperasi_id']);
                if (!$good || $good->jenis_transaksi !== 'jual' || ($good->status !== 'aktif' && !$old->contains('koperasi_id', $good->id))) {
                    throw ValidationException::withMessages(['items' => 'Barang harus merupakan barang jual yang tersedia.']);
                }
                if ($good->stok < $item['jumlah']) {
                    throw ValidationException::withMessages(['items' => 'Stok '.$good->nama_barang.' tidak cukup. Tersedia: '.$good->stok]);
                }
                $price = (float) ($old->firstWhere('koperasi_id', $good->id)?->harga_satuan ?? $good->harga_jual);
                $prepared->push(['koperasi_id' => $good->id, 'nama_barang' => $good->nama_barang, 'jumlah' => $item['jumlah'], 'harga_satuan' => $price, 'subtotal' => $price * $item['jumlah']]);
                $good->decrement('stok', $item['jumlah']);
            }
            $sale->fill(['sekolah_id' => $student->id_sekolah, 'siswa_id' => $student->id, 'tanggal' => $validated['tanggal'], 'metode_bayar' => $validated['metode_bayar'], 'catatan' => $validated['catatan'] ?? null, 'total' => $prepared->sum('subtotal')]);
            if (!$id) { $sale->kode_transaksi = $this->generateKodeTransaksi(); }
            $sale->save();
            if ($id) { $sale->details()->delete(); }
            $sale->details()->createMany($prepared->all());
            if ($sale->metode_bayar === 'dompet') {
                $walletPayment = app(\App\Services\WalletCooperativePaymentService::class)
                    ->pay($sale, 'cooperative-sale:'.$sale->getKey());
                $sale->forceFill(['wallet_payment_id' => $walletPayment->getKey()])->save();
            }
            LogAktivitas::create(['aktor_type' => 'admin', 'aktor_id' => auth()->id(), 'aktivitas' => ($id ? 'Koreksi' : 'Mencatat').' penjualan koperasi '.$sale->kode_transaksi.'. Alasan: '.($validated['alasan_koreksi'] ?? '-').'. Sebelum: '.json_encode($before).'. Sesudah: '.json_encode(['transaksi' => $sale->toArray(), 'items' => $prepared->all()]), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
            return $sale;
        });
        return redirect()->route('koperasi.penjualan.show', $sale->id)->with('success', 'Transaksi koperasi berhasil '.($id ? 'dikoreksi' : 'disimpan').'. Stok dan kas masuk telah diperbarui.');
    }

    public function store(Request $request)
    {
        return $this->saveSale($request);
    }

    public function show($id)
    {
        $penjualan = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'details.barang'])->findOrFail($id);

        return view('koperasi.penjualan.show', compact('penjualan'));
    }

    public function cetakKwitansi(Request $request, $id)
    {
        $penjualan = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'details.barang'])->findOrFail($id);

        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::guard('web')->id(),
            'aktivitas' => 'Mencetak kwitansi penjualan koperasi ' . $penjualan->kode_transaksi . ' untuk siswa: ' . ($penjualan->siswa->nama ?? '-'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('tagihan.kwitansi.receipt', [
            'receipt' => $this->receiptForSale($penjualan),
        ]);
    }

    public function unduhKwitansiPdf(Request $request, $id)
    {
        $penjualan = KoperasiPenjualan::with(['sekolah', 'siswa.kelas', 'details.barang'])->findOrFail($id);
        $receipt = $this->receiptForSale($penjualan);
        $safeNumber = Str::of((string) $receipt['number'])
            ->replace(['/', '\\'], '-')
            ->replaceMatches('/[^A-Za-z0-9._-]/', '-')
            ->trim('-')
            ->value();
        $filename = 'kwitansi-koperasi-' . ($safeNumber ?: $penjualan->id) . '.pdf';

        if (! class_exists(\Barryvdh\DomPDF\PDF::class) || ! app()->bound('dompdf.wrapper')) {
            return view('tagihan.kwitansi.receipt', [
                'receipt' => $receipt,
                'clientPdfFallback' => ! $request->boolean('preview'),
                'clientPrintFallback' => $request->boolean('preview'),
            ]);
        }

        try {
            $pdf = app('dompdf.wrapper')
                ->loadView('tagihan.kwitansi.pdf', compact('receipt'))
                ->setPaper('a5', 'portrait')
                ->setOption('isRemoteEnabled', false);
        } catch (\Throwable $exception) {
            report($exception);

            return view('tagihan.kwitansi.receipt', [
                'receipt' => $receipt,
                'clientPdfFallback' => ! $request->boolean('preview'),
                'clientPrintFallback' => $request->boolean('preview'),
            ]);
        }

        return $request->boolean('preview')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    public function destroy(Request $request, $id)
    {
        $validated = $request->validate(['alasan_pembatalan' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($request, $id, $validated) {
            $penjualan = KoperasiPenjualan::lockForUpdate()->findOrFail($id);
            $penjualan->load('details');
            $kodeTransaksi = $penjualan->kode_transaksi;
            Koperasi::whereIn('id', $penjualan->details->pluck('koperasi_id'))->orderBy('id')->lockForUpdate()->get();
            foreach ($penjualan->details as $detail) {
                Koperasi::whereKey($detail->koperasi_id)->increment('stok', $detail->jumlah);
            }

            if ($penjualan->metode_bayar === 'dompet' && $penjualan->wallet_payment_id) {
                $walletPayment = $penjualan->walletPayment()->with('journal')->first();
                if ($walletPayment?->journal && !$walletPayment->reversed_at) {
                    app(\App\Services\WalletLedgerService::class)->refund(
                        $walletPayment->journal,
                        'cooperative-refund:'.$penjualan->id,
                        ['sale_id' => $penjualan->id, 'sale_code' => $kodeTransaksi],
                    );
                    $walletPayment->forceFill([
                        'status' => 'refunded',
                        'reversed_at' => now(),
                    ])->save();
                }
            }

            $penjualan->forceFill(['cancelled_by' => auth()->id(), 'cancellation_reason' => $validated['alasan_pembatalan']])->save();
            $penjualan->delete();

            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id' => Auth::guard('web')->id(),
                'aktivitas' => 'Membatalkan / menghapus penjualan koperasi ' . $kodeTransaksi . ' dan mengembalikan stok barang. Alasan: '.$validated['alasan_pembatalan'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('koperasi.penjualan.index')
            ->with('success', 'Transaksi penjualan dibatalkan dan stok barang sudah dikembalikan.');
    }

    private function generateKodeTransaksi(): string
    {
        return app(\App\Services\ReceiptNumberGenerator::class)->cooperative();
    }

    private function receiptForSale(KoperasiPenjualan $penjualan): array
    {
        return [
            'student' => $penjualan->siswa,
            'school' => $penjualan->sekolah,
            'document_label' => 'Kwitansi koperasi',
            'number' => $penjualan->kode_transaksi,
            'payment_date' => $penjualan->tanggal,
            'receipt_date' => $penjualan->tanggal,
            'method' => match ($penjualan->metode_bayar) { 'tunai' => 'Tunai (TN)', 'transfer' => 'Transfer (TF)', 'dompet' => 'Dompet (DW)', default => 'Belum dicatat' },
            'officer' => $penjualan->posted_by_name,
            'note' => $penjualan->catatan,
            'items' => $penjualan->details->map(fn ($detail) => [
                'name' => $detail->nama_barang,
                'meta' => $detail->jumlah . ' × Rp' . number_format((float) $detail->harga_satuan, 0, ',', '.'),
                'amount' => (float) $detail->subtotal,
                'discount' => 0,
            ])->all(),
            'item_noun' => 'barang',
            'total_paid' => (float) $penjualan->total,
            'total_discount' => 0,
            'system_label' => 'Sistem Koperasi Permata Insani',
            'pdf_url' => route('koperasi.penjualan.kwitansi.pdf', $penjualan->id),
            'back_url' => route('koperasi.penjualan.show', $penjualan->id),
            'edit_url' => route('koperasi.penjualan.edit', $penjualan->id),
            'cancel_url' => route('koperasi.penjualan.destroy', $penjualan->id),
            'manage_permission' => 'koperasi.penjualan.manage',
        ];
    }
}
