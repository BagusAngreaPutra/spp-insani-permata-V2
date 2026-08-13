<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\LogAktivitas; // 👉 tambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $sekolah       = Sekolah::all();
        $tahunAjaran   = TahunAjaran::validPeriods();

        // Ambil input filter dari request
        $selectedSekolah     = $request->has('sekolah_id') ? $request->input('sekolah_id') : null;
        $selectedKelas       = $request->input('kelas_id');
        $selectedTahunAjaran = $request->input('tahun_ajaran_id');
        $search              = $request->input('search');

        // Seluruh opsi dibawa ke halaman agar filter kelas dapat mengikuti
        // pilihan sekolah tanpa request tambahan.
        $kelas = Kelas::with('sekolah')
            ->orderBy('sekolah_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        if ($selectedSekolah && $selectedKelas
            && !$kelas->contains(fn ($item) => (string) $item->id === (string) $selectedKelas
                && (string) $item->sekolah_id === (string) $selectedSekolah)) {
            $selectedKelas = null;
        }

        // Query siswa dengan relasi
        $query = \App\Models\Siswa::with(['kelas', 'sekolah', 'tahunAjaran']);

        if (!empty($selectedSekolah)) {
            $query->where('id_sekolah', $selectedSekolah);
        }
        if (!empty($selectedKelas)) {
            $query->where('kelas_id', $selectedKelas);
        }
        if (!empty($selectedTahunAjaran)) {
            $query->where('tahun_ajaran_id', $selectedTahunAjaran);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                ->orWhere('nama', 'like', "%{$search}%")
                ->orWhere('alamat', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $siswa = $query->get();

        // Log aktivitas
        \App\Models\LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => \Auth::guard('web')->id(),
            'aktivitas'  => 'Melihat daftar siswa',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('siswa.index', compact(
            'siswa', 'sekolah', 'kelas', 'tahunAjaran',
            'selectedSekolah', 'selectedKelas', 'selectedTahunAjaran', 'search'
        ));
    }


    public function create(Request $request)
    {
        $kelas       = Kelas::all();
        $sekolah     = Sekolah::all();
        $tahunAjaran = TahunAjaran::validPeriods();

        // 📌 Log aktivitas buka form create
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::guard('web')->id(),
            'aktivitas'  => 'Membuka form tambah siswa',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('siswa.create', compact('kelas','sekolah','tahunAjaran'));
    }

    public function store(Request $request)
    {
        $this->normalizeCurrencyFields($request, [
            'penghasilan_ayah',
            'penghasilan_ibu',
            'nominal_spp',
        ]);

        $request->validate([
            'id_sekolah'      => 'required|exists:sekolah,id',
            'kelas_id'        => 'required|exists:kelas,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'nis'             => 'required|unique:siswa,nis',
            'username'        => 'required|unique:siswa,username',
            'password'        => 'required|min:6',
            'nama'            => 'required|string',
            'alamat'          => 'nullable|string',
            'tanggal_lahir'   => 'nullable|date',
            'status'          => 'in:aktif,lulus,dropout',
            'nominal_spp'     => 'nullable|numeric|min:0',
        ]);

        $nominal = $request->filled('nominal_spp') ? $request->nominal_spp : 325000;

        Siswa::create([
        'id_sekolah'      => $request->id_sekolah,
        'kelas_id'        => $request->kelas_id,
        'tahun_ajaran_id' => $request->tahun_ajaran_id,
        'nis'             => $request->nis,
        'username'        => $request->username,
        'password'        => $request->password,
        'password_raw'    => $request->password,
        'nama'            => $request->nama,
        'alamat'          => $request->alamat,
        'tanggal_lahir'   => $request->tanggal_lahir,
        'status'          => $request->status ?? 'aktif',
        'nominal_spp'     => $nominal,
        'jenis_kelamin'    => $request->jenis_kelamin,
        'agama'            => $request->agama,
        'tempat_tinggal'   => $request->tempat_tinggal,
        'moda_transportasi'=> $request->moda_transportasi,
        'nama_ayah'        => $request->nama_ayah,
        'nik_ayah'         => $request->nik_ayah,
        'pekerjaan_ayah'   => $request->pekerjaan_ayah,
        'penghasilan_ayah' => $request->penghasilan_ayah,
        'nama_ibu'         => $request->nama_ibu,
        'nik_ibu'          => $request->nik_ibu,
        'pekerjaan_ibu'    => $request->pekerjaan_ibu,
        'penghasilan_ibu'  => $request->penghasilan_ibu,
        'no_telp_rumah'    => $request->no_telp_rumah,
        'no_hp'            => $request->no_hp,
        'email'            => $request->email,
    ]);

        // 📌 Log aktivitas tambah
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::guard('web')->id(),
            'aktivitas'  => 'Menambahkan siswa baru: ' . $request->nama,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Siswa $siswa)
    {
        return view('siswa.show', compact('siswa'));
    }

    public function edit(Request $request, Siswa $siswa)
    {
        $kelas       = Kelas::all();
        $sekolah     = Sekolah::all();
        $tahunAjaran = TahunAjaran::validPeriods();

        // 📌 Log aktivitas buka form edit
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::guard('web')->id(),
            'aktivitas'  => 'Membuka form edit siswa: ' . $siswa->nama,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return view('siswa.edit', compact('siswa','kelas','sekolah','tahunAjaran'));
    }

    public function update(Request $request, Siswa $siswa)
    {
        $this->normalizeCurrencyFields($request, [
            'penghasilan_ayah',
            'penghasilan_ibu',
            'nominal_spp',
        ]);

        $request->validate([
            'id_sekolah'      => 'required|exists:sekolah,id',
            'kelas_id'        => 'required|exists:kelas,id',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'nis'             => 'required|unique:siswa,nis,' . $siswa->id,
            'username'        => 'required|unique:siswa,username,' . $siswa->id,
            'password'        => 'nullable|min:6',
            'nama'            => 'required|string',
            'alamat'          => 'nullable|string',
            'tanggal_lahir'   => 'nullable|date',
            'status'          => 'in:aktif,lulus,dropout',
            'nominal_spp'     => 'nullable|numeric|min:0',
        ]);

        $nominal = $request->filled('nominal_spp') ? $request->nominal_spp : 325000;

        $dataUpdate = [
            'id_sekolah'       => $request->id_sekolah,
            'kelas_id'         => $request->kelas_id,
            'tahun_ajaran_id'  => $request->tahun_ajaran_id,
            'nis'              => $request->nis,
            'username'         => $request->username,
            'nama'             => $request->nama,
            'alamat'           => $request->alamat,
            'tanggal_lahir'    => $request->tanggal_lahir,
            'status'           => $request->status ?? 'aktif',
            'nominal_spp'      => $nominal,
            'jenis_kelamin'    => $request->jenis_kelamin,
            'agama'            => $request->agama,
            'tempat_tinggal'   => $request->tempat_tinggal,
            'moda_transportasi'=> $request->moda_transportasi,
            'nama_ayah'        => $request->nama_ayah,
            'nik_ayah'         => $request->nik_ayah,
            'pekerjaan_ayah'   => $request->pekerjaan_ayah,
            'penghasilan_ayah' => $request->penghasilan_ayah,
            'nama_ibu'         => $request->nama_ibu,
            'nik_ibu'          => $request->nik_ibu,
            'pekerjaan_ibu'    => $request->pekerjaan_ibu,
            'penghasilan_ibu'  => $request->penghasilan_ibu,
            'no_telp_rumah'    => $request->no_telp_rumah,
            'no_hp'            => $request->no_hp,
            'email'            => $request->email,
        ];

       
        if ($request->filled('password')) {
            try {
                // 🔥 Simpan password yang sudah di-hash
                $dataUpdate['password'] = $request->password;

                // 🔥 Simpan juga password asli ke kolom password_raw
                $dataUpdate['password_raw'] = $request->password;

                // ✅ Tambahkan log info bahwa password akan diupdate
                Log::info('Update password siswa', [
                    'siswa_id' => $siswa->id,
                    'username' => $siswa->username,
                    'aktor_admin' => Auth::guard('web')->id(),
                    'ip' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ❌ Kalau ada error, catat ke log Laravel
                Log::error('Gagal update password siswa', [
                    'siswa_id' => $siswa->id,
                    'username' => $siswa->username,
                    'error' => $e->getMessage(),
                    'aktor_admin' => Auth::guard('web')->id(),
                    'ip' => $request->ip(),
                ]);
            }
        }

        // 📌 Simpan data lama sebelum update
        $oldData = $siswa->only(array_keys($dataUpdate));

        // 📌 Update data
        $siswa->update($dataUpdate);

        // 📌 Bandingkan perubahan
        $changes = [];
        foreach ($dataUpdate as $field => $newValue) {
            // abaikan password di detail log
            if ($field === 'password') continue;

            $oldValue = $oldData[$field] ?? '';
            // bandingkan nilai lama dan baru
            if ($oldValue != $newValue) {
                $label = ucfirst(str_replace('_', ' ', $field));
                $changes[] = "{$label}: '{$oldValue}' → '{$newValue}'";
            }
        }

        $detailLog = count($changes) > 0
            ? implode(', ', $changes)
            : 'Tidak ada perubahan data.';

        // 📌 Log aktivitas update (lebih detail)
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::guard('web')->id(),
            'aktivitas'  => "Memperbarui data siswa ID {$siswa->id} ({$siswa->nama}). Perubahan: {$detailLog}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diupdate.');
    }


    public function destroy(Request $request, Siswa $siswa)
    {
        $nama = $siswa->nama;
        $siswa->delete();

        // 📌 Log aktivitas hapus
        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id'   => Auth::guard('web')->id(),
            'aktivitas'  => 'Menghapus data siswa: ' . $nama,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }
    public function getBySekolah($sekolah_id)
    {
        $kelas = Kelas::where('sekolah_id', $sekolah_id)->get(['id', 'tingkat', 'nama_kelas']);
        return response()->json($kelas);
    }
}
