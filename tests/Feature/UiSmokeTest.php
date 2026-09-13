<?php

namespace Tests\Feature;

use App\Http\Controllers\TagihanController;
use App\Models\Admin;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Pemasukan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_entry_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Pilih akses masuk Anda');
        $this->get('/login')->assertOk()->assertSee('Masuk ke dashboard');
        $this->get('/siswa/login')->assertOk()->assertSee('Masuk ke portal siswa');
    }

    public function test_admin_dashboard_renders_with_the_new_shell(): void
    {
        $admin = Admin::query()->firstOrFail();

        $this->actingAs($admin, 'web')
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Permata Insani')
            ->assertSee('Ringkasan')
            ->assertSee('Cari menu')
            ->assertSeeInOrder([
                '<span>Pemasukan</span>',
                '<span>Tagihan Siswa</span>',
                '<span>Koperasi</span>',
                '<span>Pemasukan Lainnya</span>',
            ], false)
            ->assertSee('Semua Transaksi')
            ->assertSee('Laporan Transaksi')
            ->assertSee('fa-school', false)
            ->assertSee('fa-file-import', false)
            ->assertSee('app-sidebar-footer', false);
    }

    public function test_primary_admin_feature_pages_render(): void
    {
        $admin = Admin::query()->firstOrFail();
        $this->actingAs($admin, 'web');

        $routes = [
            'sekolah.index',
            'tahun_ajaran.index',
            'kelas.index',
            'siswa.index',
            'admin.index',
            'jenis_pembayaran.index',
            'tagihan.index.grouped',
            'riwayat.index',
            'koperasi.index',
            'koperasi.penjualan.index',
            'pemasukan.index',
            'pengeluaran.index',
            'keuangan.kas.index',
            'kenaikan.index',
            'kelulusan.index',
            'log_aktivitas.index',
            'import.form',
            'export_excel.index',
            'backup.index',
            'laporan.tagihan_siswa',
            'laporan.pemasukan',
            'laporan.koperasi',
            'laporan.transaksi',
        ];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirection(),
                "{$routeName} returned HTTP {$response->getStatusCode()}"
            );
            if ($response->isSuccessful()) {
                $response
                    ->assertSee('app-sidebar', false)
                    ->assertSee('css/permata-system.css', false);
            }
        }
    }

    public function test_primary_admin_create_forms_render(): void
    {
        $admin = Admin::query()->firstOrFail();
        $this->actingAs($admin, 'web');

        foreach ([
            'sekolah.create',
            'tahun_ajaran.create',
            'kelas.create',
            'siswa.create',
            'admin.create',
            'jenis_pembayaran.create',
            'koperasi.create',
            'koperasi.penjualan.create',
            'pemasukan.create',
            'pengeluaran.create',
        ] as $routeName) {
            $response = $this->get(route($routeName));
            $this->assertTrue(
                $response->isSuccessful(),
                "{$routeName} returned HTTP {$response->getStatusCode()}"
            );
            $response
                ->assertSee('app-sidebar', false)
                ->assertSee('css/permata-system.css', false);
        }
    }

    public function test_tagihan_workspace_exposes_the_primary_admin_actions(): void
    {
        $admin = Admin::query()->firstOrFail();

        $response = $this->actingAs($admin, 'web')
            ->get(route('tagihan.index.grouped'));

        $response
            ->assertOk()
            ->assertSee('Tagihan siswa')
            ->assertSee('Daftar siswa')
            ->assertSee('Sisa tagihan')
            ->assertSee('Semua sekolah')
            ->assertSee('Semua kelas')
            ->assertSee('Cari nama, NIS, sekolah, atau kelas')
            ->assertSee('billing-scope-form', false)
            ->assertSee('Buat tagihan');

        $this->assertNull($response->viewData('selectedSekolah'));
        $this->assertNull($response->viewData('selectedKelas'));
        $this->assertCount(Siswa::query()->count(), $response->viewData('studentRows'));
        $this->assertCount(Kelas::query()->count(), $response->viewData('availableClasses'));
    }

    public function test_tagihan_workspace_filters_school_and_class_without_forcing_the_first_option(): void
    {
        $admin = Admin::query()->firstOrFail();
        $class = Kelas::query()->whereHas('siswa')->with('sekolah')->firstOrFail();
        $this->actingAs($admin, 'web');

        $schoolResponse = $this->get(route('tagihan.index.grouped', [
            'sekolah' => $class->sekolah_id,
        ]));

        $schoolResponse->assertOk();
        $this->assertSame($class->sekolah_id, $schoolResponse->viewData('selectedSekolah')->id);
        $this->assertNull($schoolResponse->viewData('selectedKelas'));
        $this->assertCount(
            Siswa::query()->where('id_sekolah', $class->sekolah_id)->count(),
            $schoolResponse->viewData('studentRows')
        );

        $classResponse = $this->get(route('tagihan.index.grouped', [
            'sekolah' => $class->sekolah_id,
            'kelas' => $class->id,
        ]));

        $classResponse->assertOk();
        $this->assertSame($class->id, $classResponse->viewData('selectedKelas')->id);
        $this->assertCount(
            Siswa::query()->where('kelas_id', $class->id)->count(),
            $classResponse->viewData('studentRows')
        );
    }

    public function test_transaction_history_uses_aligned_filters_and_list_rows(): void
    {
        $admin = Admin::query()->firstOrFail();
        $this->actingAs($admin, 'web');

        $response = $this->get(route('riwayat.index'));

        $response
            ->assertOk()
            ->assertSee('Riwayat transaksi')
            ->assertSee('Daftar transaksi')
            ->assertSee('Semua sekolah')
            ->assertSee('Semua kelas')
            ->assertSee('Semua transaksi')
            ->assertSee('Tagihan siswa')
            ->assertSee('Koperasi')
            ->assertSee('Pemasukan lainnya')
            ->assertSee('Cari transaksi')
            ->assertSee('Rincian tagihan')
            ->assertSee('history-filter-form', false)
            ->assertSee('history-list-head', false)
            ->assertSee('history-row-summary', false)
            ->assertSee('history-summary-type is-school', false)
            ->assertSee('history-summary-type is-cooperative', false)
            ->assertSee('history-summary-type is-income', false);

        $this->assertCount(Kelas::query()->count(), $response->viewData('kelasList'));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('transaksi'));

        $payment = Pembayaran::query()->with('siswa')->first();
        if (!$payment?->siswa?->kelas_id) {
            return;
        }

        $filteredResponse = $this->get(route('riwayat.index', [
            'jenis_pembayaran' => 'sekolah',
            'sekolah_id' => $payment->siswa->id_sekolah,
            'kelas_id' => $payment->siswa->kelas_id,
            'search' => $payment->siswa->nis,
        ]));

        $filteredResponse->assertOk();
        $this->assertNotEmpty($filteredResponse->viewData('transaksi')->items());
        $this->assertCount(
            Kelas::query()->where('sekolah_id', $payment->siswa->id_sekolah)->count(),
            $filteredResponse->viewData('kelasList')
        );

        foreach ($filteredResponse->viewData('transaksi')->items() as $transaction) {
            $this->assertSame('sekolah', $transaction['source_type']);
            $this->assertSame($payment->siswa->id_sekolah, $transaction['siswa']->id_sekolah);
            $this->assertSame($payment->siswa->kelas_id, $transaction['siswa']->kelas_id);
        }

        $this->get(route('riwayat.index', [
            'end_date' => $payment->tanggal_bayar->format('Y-m-d'),
        ]))->assertOk();

        $income = Pemasukan::create([
            'sekolah_id' => $payment->siswa->id_sekolah,
            'tanggal' => now()->toDateString(),
            'jumlah' => 125000,
            'sumber' => 'Pemasukan uji riwayat',
            'keterangan' => 'Memastikan pemasukan lainnya tampil di riwayat transaksi.',
        ]);
        $incomeResponse = $this->get(route('riwayat.index', [
            'jenis_pembayaran' => 'pemasukan',
            'sekolah_id' => $income->sekolah_id,
            'search' => 'PM-' . $income->id,
        ]));

        $incomeResponse
            ->assertOk()
            ->assertSee('Pemasukan Lainnya')
            ->assertSee('history-row is-income', false);

        $this->assertCount(1, $incomeResponse->viewData('transaksi')->items());
        foreach ($incomeResponse->viewData('transaksi')->items() as $transaction) {
            $this->assertSame('pemasukan', $transaction['source_type']);
            $this->assertSame($income->sekolah_id, $transaction['sekolah']->id);
            $this->assertNull($transaction['siswa']);
        }
    }

    public function test_active_tagihan_flow_uses_the_redesigned_pages(): void
    {
        $admin = Admin::query()->firstOrFail();
        $siswa = Siswa::query()->whereHas('tagihan')->firstOrFail();
        $this->actingAs($admin, 'web');

        $this->get(route('tagihan.index.original'))
            ->assertRedirect(route('tagihan.index.grouped'));

        $response = $this->get(route('tagihan.proses.siswa', $siswa->id));
        $response
            ->assertOk()
            ->assertSee('Pilih tagihan')
            ->assertSee('Lanjut pembayaran')
            ->assertSee('Atur pembayaran')
            ->assertSee('payment-summary-bar', false)
            ->assertSee('payment-summary-value', false)
            ->assertSee('Tahun ajaran');

        $monthlyGroup = collect($response->viewData('tagihanList'))
            ->firstWhere('is_grouped', true);

        if ($monthlyGroup) {
            $displayedMonths = collect($monthlyGroup['bulan_tagihan'])
                ->pluck('periode')
                ->map(fn ($period) => (int) substr($period, 5, 2))
                ->values();
            $academicOrder = $displayedMonths
                ->sortBy(fn ($month) => ($month + 5) % 12)
                ->values();

            $this->assertSame($academicOrder->all(), $displayedMonths->all());

            $academicYears = collect($monthlyGroup['bulan_tagihan'])
                ->pluck('periode')
                ->map(function ($period) {
                    $year = (int) substr($period, 0, 4);
                    $month = (int) substr($period, 5, 2);

                    return $month >= 7 ? $year : $year - 1;
                })
                ->unique()
                ->values();

            $this->assertSame([$monthlyGroup['academic_year_start']], $academicYears->all());
        }

        $pembayaran = Pembayaran::query()->first();
        if ($pembayaran) {
            $this->get(route('pembayaran.kwitansi', $pembayaran->id))
                ->assertOk()
                ->assertSee('Kwitansi pembayaran')
                ->assertSee('Pembayaran diterima')
                ->assertSee('Rincian transaksi')
                ->assertSee('Jumlah diterima')
                ->assertSee('receipt-totals-area', false)
                ->assertSee('receipt-document-footer', false);
        }

        $paymentGroup = Pembayaran::query()
            ->whereNotNull('transaction_id')
            ->get()
            ->groupBy('transaction_id')
            ->first(fn ($items) => $items->count() > 1);

        if ($paymentGroup) {
            $this->get(route('pembayaran.kwitansi.grup', [
                'ids' => $paymentGroup->pluck('id')->implode(','),
            ]))
                ->assertOk()
                ->assertSee('Kwitansi gabungan')
                ->assertSee('Rincian transaksi')
                ->assertSee($paymentGroup->count() . ' tagihan');
        }
    }

    public function test_generated_billing_period_uses_the_academic_year(): void
    {
        $student = new Siswa();
        $student->setRelation('tahunAjaran', new TahunAjaran([
            'nama_tahun' => '2025/2026',
        ]));

        $controller = app(TagihanController::class);
        $method = new \ReflectionMethod($controller, 'getAcademicYearMonths');
        $method->setAccessible(true);

        $periods = collect($method->invoke($controller, $student))
            ->map(fn ($period) => $period->format('Y-m'))
            ->all();

        $this->assertSame([
            '2025-07',
            '2025-08',
            '2025-09',
            '2025-10',
            '2025-11',
            '2025-12',
            '2026-01',
            '2026-02',
            '2026-03',
            '2026-04',
            '2026-05',
            '2026-06',
        ], $periods);
    }

    public function test_primary_student_pages_render(): void
    {
        $siswa = Siswa::query()->firstOrFail();
        $this->actingAs($siswa, 'siswa');

        foreach ([
            'siswa.dashboard',
            'siswa.tagihan.index',
            'siswa.riwayat.index',
            'siswa.profil.index',
        ] as $routeName) {
            $response = $this->get(route($routeName));
            $this->assertTrue(
                $response->isSuccessful(),
                "{$routeName} returned HTTP {$response->getStatusCode()}"
            );
            $response->assertSee('app-sidebar', false);
            $this->assertTrue(
                str_contains($response->getContent(), 'css/permata-system.css')
                    || str_contains($response->getContent(), 'css/permata-student-dashboard.css'),
                "{$routeName} did not load a current student design stylesheet"
            );
        }
    }
}
