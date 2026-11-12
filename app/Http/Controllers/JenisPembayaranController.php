<?php

namespace App\Http\Controllers;

use App\Models\JenisPembayaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Services\TagihanPeriodReconciler;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JenisPembayaranController extends Controller
{
    private array $paymentTypeAcademicYearCache = [];

    // Tampilkan daftar
    public function index(Request $request)
    {
        $sekolah = Sekolah::all();
        $selectedSekolah = $request->input('sekolah_id');
        $selectedTarget = $request->input('target_type');
        $search = $request->input('search');

        $query = JenisPembayaran::with(['sekolah', 'tahunAjaran', 'siswa', 'kelas']);

        if (!empty($selectedSekolah)) {
            $query->where('sekolah_id', $selectedSekolah);
        }

        if (in_array($selectedTarget, ['all', 'specific_students', 'specific_classes'], true)) {
            if ($selectedTarget === 'all') {
                $query->where(function ($targetQuery) {
                    $targetQuery->where('target_type', 'all')
                        ->orWhereNull('target_type');
                });
            } else {
                $query->where('target_type', $selectedTarget);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pembayaran', 'like', "%{$search}%")
                  ->orWhere('tipe', 'like', "%{$search}%")
                  ->orWhereHas('tahunAjaran', fn ($tahun) => $tahun->where('nama_tahun', 'like', "%{$search}%"));
            });
        }

        $jenis = $query->paginate(10)->appends($request->query());

        return view('jenis_pembayaran.index', [
            'jenis'           => $jenis,
            'sekolah'         => $sekolah,
            'selectedSekolah' => $selectedSekolah,
            'selectedTarget'  => $selectedTarget,
            'search'          => $search,
        ]);
    }

    public function create()
    {
        $sekolah = Sekolah::all();
        $tahunAjaran = $this->getAcademicYearOptions();
        $siswa = collect();
        $kelas = collect();
        
        return view('jenis_pembayaran.create', compact('sekolah', 'tahunAjaran', 'siswa', 'kelas'));
    }

    public function store(Request $request)
    {
        $this->normalizePaymentName($request);
        $this->normalizeCurrencyFields($request, ['nominal']);
        $this->normalizeDueDateForAcademicYear($request);

        $submissionToken = (string) $request->validate([
            'submission_token' => ['required', 'uuid'],
        ])['submission_token'];

        $existingSubmission = JenisPembayaran::where('submission_token', $submissionToken)->first();

        if ($existingSubmission) {
            return redirect()->route('jenis_pembayaran.index')->with(
                'success',
                'Jenis pembayaran sudah tersimpan. Permintaan ganda tidak diproses kembali.'
            );
        }

        $validated = $request->validate([
            'sekolah_id'      => 'required|exists:sekolah,id',
            'tahun_ajaran_id' => [
                'required',
                'exists:tahun_ajaran,id',
                function ($attribute, $value, $fail) {
                    if (!TahunAjaran::find($value)?->hasValidPeriod()) {
                        $fail('Tahun ajaran yang dipilih harus berformat berurutan, misalnya 2025/2026.');
                    }
                },
            ],
            'nama_pembayaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jenis_pembayaran', 'nama_pembayaran')->where(
                    fn ($query) => $query
                        ->where('sekolah_id', $request->input('sekolah_id'))
                        ->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'))
                ),
            ],
            'tipe'            => 'required|in:sekali,bulanan,setahun,semester', // ✅ TAMBAH semester
            'nominal'         => 'required|numeric|min:0',
            'jatuh_tempo'     => $this->dueDateRules($request),
            'target_type'     => 'required|in:all,specific_students,specific_classes',
            'siswa_ids'       => 'required_if:target_type,specific_students|array',
            'siswa_ids.*'     => 'exists:siswa,id',
            'kelas_ids'       => 'required_if:target_type,specific_classes|array',
            'kelas_ids.*'     => 'exists:kelas,id',
        ], [
            'nama_pembayaran.unique' => 'Nama pembayaran tersebut sudah ada untuk sekolah dan tahun ajaran yang dipilih.',
        ]);

        $validated['submission_token'] = $submissionToken;

        try {
            DB::transaction(function () use ($request, $validated): void {
                $data = JenisPembayaran::create($validated);

                if ($request->target_type === 'specific_students' && $request->has('siswa_ids')) {
                    $data->siswa()->attach($request->siswa_ids);
                }

                if ($request->target_type === 'specific_classes' && $request->has('kelas_ids')) {
                    $data->kelas()->attach($request->kelas_ids);
                }

                // Jenis pembayaran dan seluruh tagihannya harus berhasil atau gagal bersama-sama.
                $this->_generateBillsFor($data);

                LogAktivitas::create([
                    'aktor_type' => 'admin',
                    'aktor_id'   => Auth::guard('web')->id(),
                    'aktivitas'  => 'Menambahkan jenis pembayaran: ' . $data->nama_pembayaran,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });

            return redirect()->route('jenis_pembayaran.index')->with('success', 'Jenis pembayaran berhasil ditambahkan dan tagihan sedang dibuat.');
        } catch (QueryException $e) {
            // Dua request yang benar-benar bersamaan dapat sama-sama lolos pengecekan awal.
            // Indeks unik token menjadi pengaman terakhir; request kedua dianggap sukses.
            if ($this->isDuplicateKeyException($e)
                && JenisPembayaran::where('submission_token', $submissionToken)->exists()) {
                return redirect()->route('jenis_pembayaran.index')->with(
                    'success',
                    'Jenis pembayaran sudah tersimpan. Permintaan ganda tidak diproses kembali.'
                );
            }

            report($e);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan jenis pembayaran. Silakan coba kembali.'])->withInput();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan jenis pembayaran. Silakan coba kembali.'])->withInput();
        }
    }


    public function edit($id)
    {
        $jenis = JenisPembayaran::with(['tahunAjaran', 'siswa', 'kelas'])->findOrFail($id);
        $sekolah = Sekolah::all();
        $tahunAjaran = $this->getAcademicYearOptions();

        // ✅ PERBAIKAN: Langsung filter siswa berdasarkan id_sekolah
        $siswa = Siswa::where('id_sekolah', $jenis->sekolah_id)  // <- PERBAIKAN!
                    ->get();

        $kelas = Kelas::where('sekolah_id', $jenis->sekolah_id)->get();

        return view('jenis_pembayaran.create', compact('jenis', 'sekolah', 'tahunAjaran', 'siswa', 'kelas'));
    }

    
    public function update(Request $request, $id)
    {
        $this->normalizePaymentName($request);
        $this->normalizeCurrencyFields($request, ['nominal']);
        $this->normalizeDueDateForAcademicYear($request);

        $validated = $request->validate([
            'sekolah_id'      => 'required|exists:sekolah,id',
            'tahun_ajaran_id' => [
                'required',
                'exists:tahun_ajaran,id',
                function ($attribute, $value, $fail) {
                    if (!TahunAjaran::find($value)?->hasValidPeriod()) {
                        $fail('Tahun ajaran yang dipilih harus berformat berurutan, misalnya 2025/2026.');
                    }
                },
            ],
            'nama_pembayaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jenis_pembayaran', 'nama_pembayaran')
                    ->ignore($id)
                    ->where(
                        fn ($query) => $query
                            ->where('sekolah_id', $request->input('sekolah_id'))
                            ->where('tahun_ajaran_id', $request->input('tahun_ajaran_id'))
                    ),
            ],
            'tipe'            => 'required|in:sekali,bulanan,setahun,semester', // ✅ TAMBAH semester
            'nominal'         => 'required|numeric|min:0',
            'jatuh_tempo'     => $this->dueDateRules($request),
            'target_type'     => 'required|in:all,specific_students,specific_classes',
            'siswa_ids'       => 'required_if:target_type,specific_students|array',
            'siswa_ids.*'     => 'exists:siswa,id',
            'kelas_ids'       => 'required_if:target_type,specific_classes|array',
            'kelas_ids.*'     => 'exists:kelas,id',
        ], [
            'nama_pembayaran.unique' => 'Nama pembayaran tersebut sudah ada untuk sekolah dan tahun ajaran yang dipilih.',
        ]);

        DB::beginTransaction();
        try {
            $data = JenisPembayaran::findOrFail($id);
            $data->update($validated);

            if ($request->target_type === 'specific_students') {
                $data->siswa()->sync($request->siswa_ids ?? []);
            } else {
                $data->siswa()->detach();
            }

            if ($request->target_type === 'specific_classes') {
                $data->kelas()->sync($request->kelas_ids ?? []);
            } else {
                $data->kelas()->detach();
            }

            DB::commit();

            // Generate bills automatically on update
            $this->_generateBillsFor($data);

            return redirect()->route('jenis_pembayaran.index')->with('success', 'Jenis pembayaran berhasil diupdate dan tagihan sedang dibuat ulang.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $jenis = JenisPembayaran::findOrFail($id);
            $namaPembayaran = $jenis->nama_pembayaran;

            // Hapus relasi sebelum menghapus item utama
            $jenis->siswa()->detach();
            $jenis->kelas()->detach();
            $jenis->delete();

            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id'   => Auth::guard('web')->id(),
                'aktivitas'  => 'Menghapus jenis pembayaran: ' . $namaPembayaran,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
            return redirect()->route('jenis_pembayaran.index')->with('success', 'Jenis pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }
    }

    public function getDataBySekolah($sekolahId)
    {
        // ✅ PERBAIKAN: Gunakan 'id_sekolah' bukan 'sekolah_id'
        $siswa = Siswa::where('id_sekolah', $sekolahId)  // <- PERBAIKAN!
                    ->with('kelas:id,nama_kelas,tingkat')
                    ->select('id', 'nama', 'nis', 'kelas_id')
                    ->orderBy('nama')
                    ->get()
                    ->map(function($s) {
                        return [
                            'id' => $s->id,
                            'nama' => $s->nama,
                            'nis' => $s->nis,
                            'kelas_id' => $s->kelas_id,
                            'kelas_nama' => $s->kelas ? $s->kelas->kelas : 'Kelas tidak diketahui'
                        ];
                    });

        $kelas = Kelas::where('sekolah_id', $sekolahId)  // <- Ini sudah benar
                    ->select('id', 'nama_kelas', 'tingkat')
                    ->orderBy('tingkat')
                    ->orderBy('nama_kelas')
                    ->get();

        return response()->json([
            'siswa' => $siswa,
            'kelas' => $kelas
        ]);
    }

    private function getAcademicYearOptions()
    {
        return TahunAjaran::validPeriods();
    }

    private function normalizePaymentName(Request $request): void
    {
        $request->merge([
            'nama_pembayaran' => Str::squish((string) $request->input('nama_pembayaran')),
        ]);
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    private function normalizeDueDateForAcademicYear(Request $request): void
    {
        $tahunAjaran = TahunAjaran::find($request->input('tahun_ajaran_id'));
        $bounds = $tahunAjaran?->periodBounds();

        if (!$bounds || !is_numeric($request->input('jatuh_tempo'))) {
            return;
        }

        [$startYear, $endYear] = $bounds;
        $selected = (int) $request->input('jatuh_tempo');

        if ($request->input('tipe') === 'bulanan' && $selected >= 1 && $selected <= 28) {
            $request->merge([
                'jatuh_tempo' => Carbon::create($startYear, 7, $selected)->toDateString(),
            ]);
        }

        if ($request->input('tipe') === 'semester' && $selected >= 1 && $selected <= 6) {
            $request->merge([
                'jatuh_tempo' => Carbon::create($endYear, $selected, 1)->toDateString(),
            ]);
        }
    }

    private function dueDateRules(Request $request): array
    {
        return [
            'required',
            'date',
            function ($attribute, $value, $fail) use ($request) {
                $tahunAjaran = TahunAjaran::find($request->input('tahun_ajaran_id'));
                $bounds = $tahunAjaran?->periodBounds();

                if (!$bounds) {
                    return;
                }

                [$startYear, $endYear] = $bounds;
                $dueDate = Carbon::parse($value)->startOfDay();
                $periodStart = Carbon::create($startYear, 7, 1)->startOfDay();
                $periodEnd = Carbon::create($endYear, 6, 30)->endOfDay();

                if (!$dueDate->betweenIncluded($periodStart, $periodEnd)) {
                    $fail("Tanggal jatuh tempo harus berada dalam Tahun Ajaran {$startYear}/{$endYear} (Juli {$startYear} sampai Juni {$endYear}).");
                }
            },
        ];
    }

    
    /**
     * Generate bills for a given payment type.
     *
     * @param JenisPembayaran $jenis
     * @return void
     */
    private function _generateBillsFor(JenisPembayaran $jenis): void
    {
        $jenis->loadMissing('tahunAjaran');
        app(TagihanPeriodReconciler::class)->reconcileJenis($jenis);

        foreach ($jenis->getEligibleSiswa() as $siswa) {
            $siswa->loadMissing('tahunAjaran');
            [$tahunAwal, $tahunAkhir] = $this->resolveAcademicYearBounds($siswa, $jenis);
            $namaTagihan = $jenis->nama_pembayaran . ' - Tahun Ajaran ' . $tahunAwal . '/' . $tahunAkhir;

            if ($jenis->tipe === 'bulanan') {
                $tanggalJatuhTempo = Carbon::parse($jenis->jatuh_tempo)->day;

                foreach ($this->getYearMonths($siswa, $jenis) as $bulanTagihan) {
                    $this->saveGeneratedBill(
                        [
                            'siswa_id' => $siswa->id,
                            'jenis_pembayaran_id' => $jenis->id,
                            'periode' => $bulanTagihan->format('Y-m'),
                        ],
                        [
                            'id_sekolah' => $siswa->id_sekolah,
                            'nama_tagihan' => $namaTagihan,
                            'nominal' => $jenis->nominal,
                            'tipe' => $jenis->tipe,
                            'tanggal_jatuh_tempo' => $bulanTagihan->copy()->day($tanggalJatuhTempo),
                        ]
                    );
                }

                continue;
            }

            if ($jenis->tipe === 'semester') {
                $bulanPilihan = Carbon::parse($jenis->jatuh_tempo)->month;
                $pasanganBulan = [$bulanPilihan, (($bulanPilihan + 5) % 12) + 1];
                usort($pasanganBulan, fn ($a, $b) => (($a + 5) % 12) <=> (($b + 5) % 12));

                foreach ($pasanganBulan as $index => $bulan) {
                    $tahunPeriode = $bulan >= 7 ? $tahunAwal : $tahunAkhir;

                    $this->saveGeneratedBill(
                        [
                            'siswa_id' => $siswa->id,
                            'jenis_pembayaran_id' => $jenis->id,
                            'periode' => sprintf('%04d-%02d', $tahunPeriode, $bulan),
                        ],
                        [
                            'id_sekolah' => $siswa->id_sekolah,
                            'nama_tagihan' => $namaTagihan . ' - Semester ' . ($index + 1),
                            'nominal' => $jenis->nominal,
                            'tipe' => $jenis->tipe,
                            'tanggal_jatuh_tempo' => Carbon::create($tahunPeriode, $bulan, 10),
                        ]
                    );
                }

                continue;
            }

            if (
                $jenis->tipe === 'sekali'
                && Tagihan::where('siswa_id', $siswa->id)
                    ->where('jenis_pembayaran_id', $jenis->id)
                    ->whereHas('pembayaran')
                    ->exists()
            ) {
                continue;
            }

            $identity = [
                'siswa_id' => $siswa->id,
                'jenis_pembayaran_id' => $jenis->id,
            ];

            if ($jenis->tipe === 'setahun') {
                $identity['periode'] = (string) $tahunAwal;
            }

            $this->saveGeneratedBill(
                $identity,
                [
                    'id_sekolah' => $siswa->id_sekolah,
                    'nama_tagihan' => $namaTagihan,
                    'nominal' => $jenis->nominal,
                    'tipe' => $jenis->tipe,
                    'periode' => (string) $tahunAwal,
                    'tanggal_jatuh_tempo' => $jenis->jatuh_tempo,
                ]
            );
        }
    }

    private function saveGeneratedBill(array $identity, array $attributes): void
    {
        $paymentTypeId = $identity['jenis_pembayaran_id'] ?? $attributes['jenis_pembayaran_id'] ?? null;
        if ($paymentTypeId) {
            $attributes['tahun_ajaran_id'] = $this->paymentTypeAcademicYearCache[$paymentTypeId]
                ??= JenisPembayaran::whereKey($paymentTypeId)->value('tahun_ajaran_id');
        }

        $tagihan = Tagihan::firstOrNew($identity);

        // Nominal pada tagihan yang sudah dibayar adalah rekam historis.
        // Perubahan jenis pembayaran hanya boleh mengubah nominal tagihan yang belum dibayar.
        if ($tagihan->exists && $tagihan->pembayaran()->exists()) {
            unset($attributes['nominal']);
        }

        $tagihan->fill($attributes);

        if (!$tagihan->exists) {
            $tagihan->status = 'belum';
        }

        $tagihan->save();
    }

    private function resolveAcademicYearBounds(Siswa $siswa, JenisPembayaran $jenis): array
    {
        $bounds = $jenis->tahunAjaran?->periodBounds()
            ?? $siswa->tahunAjaran?->periodBounds();

        if ($bounds) {
            return $bounds;
        }

        $tahunAwal = TahunAjaran::currentStartYear();

        return [$tahunAwal, $tahunAwal + 1];
    }

    private function getYearMonths(Siswa $siswa, JenisPembayaran $jenis): array
    {
        [$tahunAwal] = $this->resolveAcademicYearBounds($siswa, $jenis);
        $bulanPertama = Carbon::create($tahunAwal, 7, 1)->startOfDay();

        return array_map(
            fn (int $offset) => $bulanPertama->copy()->addMonths($offset),
            range(0, 11)
        );
    }

    /**
     * Generator lama dipertahankan sementara sebagai referensi kompatibilitas data.
     */
    private function _generateBillsForLegacy(JenisPembayaran $jenis)
    {
        $this->_generateBillsFor($jenis);
        return;

        $eligibleSiswa = $jenis->getEligibleSiswa();

        foreach ($eligibleSiswa as $siswa) {
            $siswa->loadMissing('tahunAjaran');

            if ($jenis->tipe === 'bulanan') {
                $dueDay = Carbon::parse($jenis->jatuh_tempo)->day;

                foreach ($this->getAcademicYearMonths($siswa) as $bulanTagihan) {
                    $periode = $bulanTagihan->format('Y-m');

                    \App\Models\Tagihan::updateOrCreate(
                        [
                            'siswa_id' => $siswa->id,
                            'jenis_pembayaran_id' => $jenis->id,
                            'periode' => $periode,
                        ],
                        [
                            'id_sekolah' => $siswa->id_sekolah,
                            'nama_tagihan' => $jenis->nama_pembayaran . ' - ' . Carbon::parse($periode)->format('F Y'),
                            'nominal' => $jenis->nominal,
                            'tipe' => $jenis->tipe,
                            'tanggal_jatuh_tempo' => $bulanTagihan->copy()->day($dueDay),
                            'status' => 'belum',
                        ]
                    );
                }
            } 
            // ✅ TAMBAH handling untuk semester
            elseif ($jenis->tipe === 'semester') {
                $jatuhTempo = Carbon::parse($jenis->jatuh_tempo);
                $bulanPertama = $jatuhTempo->month;
                $bulanKedua = $bulanPertama + 6; // 6 bulan kemudian

                // Semester 1
                $periode1 = Carbon::now()->year . '-' . str_pad($bulanPertama, 2, '0', STR_PAD_LEFT);
                \App\Models\Tagihan::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'jenis_pembayaran_id' => $jenis->id,
                        'periode' => $periode1,
                    ],
                    [
                        'id_sekolah' => $siswa->id_sekolah,
                        'nama_tagihan' => $jenis->nama_pembayaran . ' - Semester 1',
                        'nominal' => $jenis->nominal,
                        'tipe' => $jenis->tipe,
                        'tanggal_jatuh_tempo' => Carbon::parse($periode1)->day(10),
                        'status' => 'belum',
                    ]
                );

                // Semester 2
                $periode2 = Carbon::now()->year . '-' . str_pad($bulanKedua, 2, '0', STR_PAD_LEFT);
                \App\Models\Tagihan::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'jenis_pembayaran_id' => $jenis->id,
                        'periode' => $periode2,
                    ],
                    [
                        'id_sekolah' => $siswa->id_sekolah,
                        'nama_tagihan' => $jenis->nama_pembayaran . ' - Semester 2',
                        'nominal' => $jenis->nominal,
                        'tipe' => $jenis->tipe,
                        'tanggal_jatuh_tempo' => Carbon::parse($periode2)->day(10),
                        'status' => 'belum',
                    ]
                );
            }
            else { // For 'sekali', 'setahun', etc.
                \App\Models\Tagihan::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'jenis_pembayaran_id' => $jenis->id,
                    ],
                    [
                        'id_sekolah' => $siswa->id_sekolah,
                        'nama_tagihan' => $jenis->nama_pembayaran,
                        'nominal' => $jenis->nominal,
                        'tipe' => $jenis->tipe,
                        'periode' => Carbon::now()->format('Y-m'),
                        'tanggal_jatuh_tempo' => $jenis->jatuh_tempo,
                        'status' => 'belum',
                    ]
                );
            }
        }
    }

    private function getAcademicYearBounds(Siswa $siswa): array
    {
        $bounds = $siswa->tahunAjaran?->periodBounds();

        if ($bounds) {
            return $bounds;
        }

        $startYear = TahunAjaran::currentStartYear();

        return [$startYear, $startYear + 1];
    }

    private function getAcademicYearMonths(Siswa $siswa): array
    {
        [$startYear] = $this->getAcademicYearBounds($siswa);
        $firstMonth = Carbon::create($startYear, 7, 1)->startOfDay();

        return array_map(
            fn (int $offset) => $firstMonth->copy()->addMonths($offset),
            range(0, 11)
        );
    }
}
