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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array',
            'items.*.koperasi_id' => 'nullable|exists:koperasi,id',
            'items.*.jumlah' => 'nullable|integer|min:1',
        ]);

        $items = collect($validated['items'])
            ->filter(fn ($item) => !empty($item['koperasi_id']) && !empty($item['jumlah']))
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal satu barang yang dibeli.',
            ]);
        }

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        $penjualan = DB::transaction(function () use ($request, $validated, $items, $siswa) {
            $kodeTransaksi = $this->generateKodeTransaksi();
            $total = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $barang = Koperasi::whereKey($item['koperasi_id'])->lockForUpdate()->firstOrFail();
                $jumlah = (int) $item['jumlah'];

                if ($barang->status !== 'aktif') {
                    throw ValidationException::withMessages([
                        'items' => "Barang {$barang->nama_barang} sedang nonaktif.",
                    ]);
                }

                if ($barang->jenis_transaksi !== 'jual') {
                    throw ValidationException::withMessages([
                        'items' => "{$barang->nama_barang} merupakan barang sewa dan tidak dapat dicatat sebagai penjualan.",
                    ]);
                }

                if ($barang->stok < $jumlah) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$barang->nama_barang} tidak cukup. Stok tersedia: {$barang->stok}.",
                    ]);
                }

                $subtotal = $jumlah * (float) $barang->harga_jual;
                $total += $subtotal;

                $preparedItems[] = [
                    'barang' => $barang,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $barang->harga_jual,
                    'subtotal' => $subtotal,
                ];
            }

            $penjualan = KoperasiPenjualan::create([
                'sekolah_id' => $siswa->id_sekolah,
                'siswa_id' => $siswa->id,
                'kode_transaksi' => $kodeTransaksi,
                'tanggal' => $validated['tanggal'],
                'total' => $total,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($preparedItems as $preparedItem) {
                $barang = $preparedItem['barang'];
                $barang->decrement('stok', $preparedItem['jumlah']);

                $penjualan->details()->create([
                    'koperasi_id' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'jumlah' => $preparedItem['jumlah'],
                    'harga_satuan' => $preparedItem['harga_satuan'],
                    'subtotal' => $preparedItem['subtotal'],
                ]);
            }

            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id' => Auth::guard('web')->id(),
                'aktivitas' => 'Mencatat penjualan koperasi ' . $kodeTransaksi . ' untuk siswa: ' . $siswa->nama,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $penjualan;
        });

        return redirect()
            ->route('koperasi.penjualan.show', $penjualan->id)
            ->with('success', 'Transaksi penjualan koperasi berhasil disimpan dan stok barang sudah berkurang.');
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

        return view('koperasi.penjualan.kwitansi', compact('penjualan'));
    }

    public function destroy(Request $request, $id)
    {
        $penjualan = KoperasiPenjualan::with('details')->findOrFail($id);
        $kodeTransaksi = $penjualan->kode_transaksi;

        DB::transaction(function () use ($request, $penjualan, $kodeTransaksi) {
            foreach ($penjualan->details as $detail) {
                Koperasi::whereKey($detail->koperasi_id)->increment('stok', $detail->jumlah);
            }

            $penjualan->delete();

            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id' => Auth::guard('web')->id(),
                'aktivitas' => 'Membatalkan penjualan koperasi ' . $kodeTransaksi . ' dan mengembalikan stok barang',
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
        $prefix = 'KOP-' . date('Ymd') . '-';
        $lastNumber = KoperasiPenjualan::where('kode_transaksi', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(kode_transaksi, ?) AS UNSIGNED)) as nomor', [strlen($prefix) + 1])
            ->value('nomor');

        return $prefix . str_pad(((int) $lastNumber) + 1, 4, '0', STR_PAD_LEFT);
    }
}
