<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\JenisPembayaran;
use App\Models\Kelas;
use App\Models\Koperasi;
use App\Models\KoperasiPenjualan;
use App\Models\Pembayaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Black-box HTTP checks against a populated database.
 *
 * DatabaseTransactions is intentional: the suite may exercise destructive
 * buttons, but every change is rolled back at the end of each scenario.
 */
class BlackBoxSystemTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): Admin
    {
        return Admin::query()
            ->whereIn('role', ['super_admin', 'administrator'])
            ->first() ?? Admin::query()->firstOrFail();
    }

    private function asAdmin(): self
    {
        return $this->actingAs($this->admin(), 'web');
    }

    public function test_guest_access_and_invalid_credentials_are_rejected(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('sekolah.index'))->assertRedirect(route('login'));
        $this->get(route('siswa.dashboard'))->assertRedirect(route('siswa.login'));

        $this->from(route('login'))->post(route('login.store'), [
            'username' => '__tidak_ada__',
            'password' => 'salah-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');
        $this->assertGuest('web');

        $this->from(route('siswa.login'))->post(route('siswa.login.store'), [
            'username' => '__tidak_ada__',
            'password' => 'salah-password',
        ])->assertRedirect(route('siswa.login'))->assertSessionHasErrors('username');
        $this->assertGuest('siswa');
    }

    public function test_admin_without_permissions_cannot_open_restricted_features(): void
    {
        $admin = Admin::create([
            'nama_admin' => 'Black Box Restricted',
            'username' => 'bb-restricted-' . Str::lower(Str::random(8)),
            'password' => Hash::make('password-test'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'web')->get(route('sekolah.index'))->assertForbidden();
        $this->actingAs($admin, 'web')->get(route('tagihan.index.grouped'))->assertForbidden();
        $this->actingAs($admin, 'web')->get(route('laporan.pembayaran'))->assertForbidden();
    }

    public function test_required_fields_and_invalid_filter_ranges_are_rejected_without_server_errors(): void
    {
        $this->asAdmin();

        $cases = [
            ['sekolah.store', [], ['nama_sekolah', 'kode_sekolah', 'alamat']],
            ['tahun_ajaran.store', [], ['nama_tahun']],
            ['kelas.store', [], ['sekolah_id', 'nama_kelas', 'tingkat']],
            ['siswa.store', [], ['id_sekolah', 'kelas_id', 'tahun_ajaran_id', 'nis', 'username', 'password', 'nama']],
            ['admin.store', [], ['nama_admin', 'username', 'password', 'role']],
            ['jenis_pembayaran.store', [], ['submission_token']],
            ['koperasi.store', [], ['sekolah_id', 'nama_barang', 'kategori', 'jenis_transaksi', 'satuan', 'harga_jual', 'stok', 'stok_minimum', 'status']],
            ['koperasi.penjualan.store', [], ['siswa_id', 'tanggal', 'items']],
            ['pemasukan.store', [], ['sekolah_id', 'tanggal', 'jumlah']],
            ['pengeluaran.store', [], ['sekolah_id', 'tanggal', 'jumlah']],
            ['pembayaran.store', [], ['siswa_id', 'tagihan_id', 'jumlah_bayar', 'tanggal_bayar']],
            ['tagihan.proses.multi', [], ['siswa_id', 'pembayaran', 'tanggal_bayar', 'tanggal_kwitansi', 'metode_bayar']],
        ];

        foreach ($cases as [$routeName, $payload, $errors]) {
            $this->from(route($routeName))->post(route($routeName), $payload)
                ->assertRedirect()
                ->assertSessionHasErrors($errors);
        }

        $this->from(route('riwayat.index'))->get(route('riwayat.index', [
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-12',
        ]))->assertRedirect(route('riwayat.index'))->assertSessionHasErrors('end_date');
    }

    public function test_school_and_class_can_be_created_updated_and_deleted_as_one_form(): void
    {
        $this->asAdmin();
        $year = TahunAjaran::query()->whereNotNull('nama_tahun')->firstOrFail();
        $code = 'BB' . Str::upper(Str::random(6));

        $this->post(route('sekolah.store'), [
            'nama_sekolah' => 'Sekolah Uji Black Box',
            'kode_sekolah' => $code,
            'alamat' => 'Alamat pengujian',
            'durasi_pendidikan' => 6,
            'kelas' => [
                ['tingkat' => 'none', 'nama_kelas' => 'Kelompok Uji', 'tahun_ajaran_id' => $year->id],
                ['tingkat' => '1', 'nama_kelas' => 'A', 'tahun_ajaran_id' => $year->id],
            ],
        ])->assertRedirect(route('sekolah.index'))->assertSessionHas('success');

        $school = Sekolah::query()->where('kode_sekolah', $code)->firstOrFail();
        $this->assertSame(2, $school->kelas()->count());
        $class = $school->kelas()->where('nama_kelas', 'A')->firstOrFail();

        $this->put(route('sekolah.update', $school), [
            'nama_sekolah' => 'Sekolah Uji Black Box Diperbarui',
            'kode_sekolah' => $code,
            'alamat' => 'Alamat pengujian baru',
            'durasi_pendidikan' => 6,
            'kelas' => [
                ['id' => $class->id, 'tingkat' => '2', 'nama_kelas' => 'B', 'tahun_ajaran_id' => $year->id],
            ],
        ])->assertRedirect(route('sekolah.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('sekolah', ['id' => $school->id, 'nama_sekolah' => 'Sekolah Uji Black Box Diperbarui']);
        $this->assertDatabaseHas('kelas', ['id' => $class->id, 'tingkat' => 2, 'nama_kelas' => 'B']);

        $student = Siswa::create([
            'id_sekolah' => $school->id,
            'kelas_id' => $class->id,
            'tahun_ajaran_id' => $year->id,
            'nis' => 'BB-' . Str::upper(Str::random(8)),
            'nama' => 'Siswa Cascade Black Box',
            'username' => 'bb-student-' . Str::lower(Str::random(8)),
            'password' => 'password-test',
            'password_raw' => 'password-test',
            'status' => 'aktif',
            'nominal_spp' => 100000,
        ]);
        $bill = Tagihan::create([
            'siswa_id' => $student->id,
            'id_sekolah' => $school->id,
            'tahun_ajaran_id' => $year->id,
            'nama_tagihan' => 'Cascade Black Box',
            'nominal' => 100000,
            'tipe' => 'sekali',
            'periode' => 'Cascade',
            'tanggal_jatuh_tempo' => '2026-08-13',
            'status' => 'belum',
        ]);
        $payment = Pembayaran::create([
            'sekolah_id' => $school->id,
            'siswa_id' => $student->id,
            'tagihan_id' => $bill->id,
            'jumlah_bayar' => 50000,
            'tanggal_bayar' => '2026-08-13',
            'tanggal_kwitansi' => '2026-08-13',
            'metode_bayar' => 'tunai',
        ]);

        $this->delete(route('sekolah.destroy', $school))
            ->assertRedirect(route('sekolah.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('sekolah', ['id' => $school->id]);
        $this->assertDatabaseMissing('kelas', ['sekolah_id' => $school->id]);
        $this->assertDatabaseMissing('siswa', ['id' => $student->id]);
        $this->assertDatabaseMissing('tagihan', ['id' => $bill->id]);
        $this->assertDatabaseMissing('pembayaran', ['id' => $payment->id]);
    }

    public function test_payment_type_submission_is_idempotent_and_does_not_duplicate_bills(): void
    {
        $this->asAdmin();
        $student = Siswa::query()->whereNotNull('tahun_ajaran_id')->firstOrFail();
        $year = TahunAjaran::findOrFail($student->tahun_ajaran_id);
        [$startYear] = $year->periodBounds();
        $name = 'Black Box Bulanan ' . Str::upper(Str::random(6));
        $token = (string) Str::uuid();
        $payload = [
            'submission_token' => $token,
            'sekolah_id' => $student->id_sekolah,
            'tahun_ajaran_id' => $year->id,
            'nama_pembayaran' => $name,
            'tipe' => 'bulanan',
            'nominal' => 'Rp 25.000',
            'jatuh_tempo' => $startYear . '-07-10',
            'target_type' => 'specific_students',
            'siswa_ids' => [$student->id],
        ];

        $this->post(route('jenis_pembayaran.store'), $payload)
            ->assertRedirect(route('jenis_pembayaran.index'))
            ->assertSessionHas('success');
        $this->post(route('jenis_pembayaran.store'), $payload)
            ->assertRedirect(route('jenis_pembayaran.index'))
            ->assertSessionHas('success');

        $type = JenisPembayaran::query()->where('submission_token', $token)->firstOrFail();
        $this->assertSame(1, JenisPembayaran::query()->where('submission_token', $token)->count());
        $this->assertSame(12, Tagihan::query()->where('jenis_pembayaran_id', $type->id)->count());

        $duplicate = $payload;
        $duplicate['submission_token'] = (string) Str::uuid();
        $this->from(route('jenis_pembayaran.create'))
            ->post(route('jenis_pembayaran.store'), $duplicate)
            ->assertRedirect(route('jenis_pembayaran.create'))
            ->assertSessionHasErrors('nama_pembayaran');
        $this->assertSame(1, JenisPembayaran::query()
            ->where('sekolah_id', $student->id_sekolah)
            ->where('tahun_ajaran_id', $year->id)
            ->where('nama_pembayaran', $name)
            ->count());
    }

    public function test_multi_payment_rejects_cross_student_and_overpayment_then_accepts_valid_payment_and_correction(): void
    {
        $this->asAdmin();
        $students = Siswa::query()->whereHas('sekolah')->limit(2)->get();
        $this->assertCount(2, $students, 'Pengujian membutuhkan dua siswa pada data acuan.');
        $student = $students[0];
        $other = $students[1];
        $bill = Tagihan::create([
            'siswa_id' => $student->id,
            'id_sekolah' => $student->id_sekolah,
            'tahun_ajaran_id' => $student->tahun_ajaran_id,
            'nama_tagihan' => 'Tagihan Black Box',
            'nominal' => 150000,
            'tipe' => 'sekali',
            'periode' => 'Uji',
            'tanggal_jatuh_tempo' => '2026-08-10',
            'status' => 'belum',
        ]);
        $base = [
            'tanggal_bayar' => '2026-08-10',
            'tanggal_kwitansi' => '2026-08-13',
            'metode_bayar' => 'tunai',
        ];

        $this->from(route('tagihan.proses.siswa', $other->id))->post(route('tagihan.proses.multi'), $base + [
            'siswa_id' => $other->id,
            'pembayaran' => [$bill->id => 100000],
        ])->assertRedirect()->assertSessionHasErrors('general');
        $this->assertDatabaseMissing('pembayaran', ['tagihan_id' => $bill->id]);

        $this->from(route('tagihan.proses.siswa', $student->id))->post(route('tagihan.proses.multi'), $base + [
            'siswa_id' => $student->id,
            'pembayaran' => [$bill->id => 200000],
        ])->assertRedirect()->assertSessionHasErrors('pembayaran');
        $this->assertDatabaseMissing('pembayaran', ['tagihan_id' => $bill->id]);

        $response = $this->post(route('tagihan.proses.multi'), $base + [
            'siswa_id' => $student->id,
            'pembayaran' => [$bill->id => 150000],
        ]);
        $payment = Pembayaran::query()->where('tagihan_id', $bill->id)->firstOrFail();
        $response->assertRedirect(route('pembayaran.kwitansi.grup', ['ids' => (string) $payment->id]));
        $this->assertSame('lunas', $bill->fresh()->status);
        $this->assertSame('2026-08-10', $payment->tanggal_bayar->toDateString());
        $this->assertSame('2026-08-13', $payment->tanggal_kwitansi->toDateString());

        $this->get(route('pembayaran.kwitansi', $payment))->assertOk()->assertSee('Tagihan Black Box');
        $pdf = $this->get(route('pembayaran.kwitansi.pdf', $payment));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
        $this->assertGreaterThan(1000, strlen($pdf->getContent()));

        $this->put(route('pembayaran.koreksi.update', $payment), [
            'siswa_id' => $student->id,
            'tanggal_bayar' => '2026-08-09',
            'tanggal_kwitansi' => '2026-08-14',
            'metode_bayar' => 'transfer',
            'keterangan' => 'Dikoreksi melalui black box',
            'alasan_koreksi' => 'Metode dan tanggal transaksi salah input.',
        ])->assertRedirect(route('riwayat.index'))->assertSessionHas('success');
        $payment->refresh();
        $this->assertSame('transfer', $payment->metode_bayar);
        $this->assertSame('2026-08-09', $payment->tanggal_bayar->toDateString());
        $this->assertSame('2026-08-14', $payment->tanggal_kwitansi->toDateString());
    }

    public function test_cooperative_stock_sale_receipt_and_cancellation_are_consistent(): void
    {
        $this->asAdmin();
        $student = Siswa::query()->firstOrFail();
        $code = 'BB-' . Str::upper(Str::random(8));

        $this->post(route('koperasi.store'), [
            'sekolah_id' => $student->id_sekolah,
            'kode_barang' => $code,
            'nama_barang' => 'Barang Black Box',
            'kategori' => 'alat_tulis',
            'jenis_transaksi' => 'jual',
            'satuan' => 'pcs',
            'harga_beli' => 'Rp 5.000',
            'harga_jual' => 'Rp 10.000',
            'stok' => 10,
            'stok_minimum' => 3,
            'status' => 'aktif',
        ])->assertRedirect(route('koperasi.index'))->assertSessionHas('success');
        $item = Koperasi::query()->where('kode_barang', $code)->firstOrFail();
        $this->assertSame('10000.00', $item->harga_jual);
        $this->assertSame('aman', $item->status_stok);

        $this->from(route('koperasi.stok.edit', $item))->put(route('koperasi.stok.update', $item), [
            'tipe' => 'kurang', 'jumlah' => 11,
        ])->assertRedirect()->assertSessionHasErrors('jumlah');
        $this->assertSame(10, $item->fresh()->stok);

        $this->put(route('koperasi.stok.update', $item), [
            'tipe' => 'tambah', 'jumlah' => 2, 'catatan' => 'Uji tambah stok',
        ])->assertRedirect(route('koperasi.index'))->assertSessionHas('success');
        $this->assertSame(12, $item->fresh()->stok);

        $this->from(route('koperasi.penjualan.create'))->post(route('koperasi.penjualan.store'), [
            'siswa_id' => $student->id,
            'tanggal' => '2026-08-13',
            'items' => [['koperasi_id' => $item->id, 'jumlah' => 99]],
        ])->assertRedirect()->assertSessionHasErrors('items');
        $this->assertSame(12, $item->fresh()->stok);

        $response = $this->post(route('koperasi.penjualan.store'), [
            'siswa_id' => $student->id,
            'tanggal' => '2026-08-13',
            'catatan' => 'Penjualan uji',
            'items' => [['koperasi_id' => $item->id, 'jumlah' => 2]],
        ]);
        $sale = KoperasiPenjualan::query()->whereHas('details', fn ($query) => $query->where('koperasi_id', $item->id))->firstOrFail();
        $response->assertRedirect(route('koperasi.penjualan.show', $sale));
        $this->assertSame(10, $item->fresh()->stok);
        $this->assertEquals(20000, (float) $sale->total);
        $this->get(route('koperasi.penjualan.show', $sale))->assertOk()->assertSee('Barang Black Box');
        $this->get(route('koperasi.penjualan.kwitansi', $sale))->assertOk()->assertSee('Barang Black Box');

        $this->delete(route('koperasi.penjualan.destroy', $sale))
            ->assertRedirect(route('koperasi.penjualan.index'))
            ->assertSessionHas('success');
        $this->assertSame(12, $item->fresh()->stok);
        $this->assertDatabaseMissing('koperasi_penjualan', ['id' => $sale->id]);
    }

    public function test_single_payment_endpoint_enforces_bill_owner_and_remaining_balance(): void
    {
        $this->asAdmin();
        $students = Siswa::query()->whereHas('sekolah')->limit(2)->get();
        $this->assertCount(2, $students, 'Pengujian membutuhkan dua siswa pada data acuan.');
        $owner = $students[0];
        $other = $students[1];
        $bill = Tagihan::create([
            'siswa_id' => $owner->id,
            'id_sekolah' => $owner->id_sekolah,
            'tahun_ajaran_id' => $owner->tahun_ajaran_id,
            'nama_tagihan' => 'Tagihan Tunggal Black Box',
            'nominal' => 100000,
            'tipe' => 'sekali',
            'periode' => 'Tunggal',
            'tanggal_jatuh_tempo' => '2026-08-10',
            'status' => 'belum',
        ]);
        $payload = [
            'tagihan_id' => $bill->id,
            'jumlah_bayar' => 100000,
            'tanggal_bayar' => '2026-08-10',
            'tanggal_kwitansi' => '2026-08-13',
            'metode_bayar' => 'transfer',
        ];

        $this->from(route('tagihan.proses.siswa', $other))->post(route('pembayaran.store'), $payload + [
            'siswa_id' => $other->id,
        ])->assertRedirect()->assertSessionHasErrors('siswa_id');
        $this->assertDatabaseMissing('pembayaran', ['tagihan_id' => $bill->id]);

        $tooLarge = $payload;
        $tooLarge['siswa_id'] = $owner->id;
        $tooLarge['jumlah_bayar'] = 100001;
        $this->from(route('tagihan.proses.siswa', $owner))->post(route('pembayaran.store'), $tooLarge)
            ->assertRedirect()->assertSessionHasErrors('jumlah_bayar');
        $this->assertDatabaseMissing('pembayaran', ['tagihan_id' => $bill->id]);

        $this->from(route('tagihan.proses.siswa', $owner))->post(route('pembayaran.store'), $payload + [
            'siswa_id' => $owner->id,
        ])->assertRedirect()->assertSessionHas('success');

        $payment = Pembayaran::query()->where('tagihan_id', $bill->id)->firstOrFail();
        $this->assertSame($owner->id, $payment->siswa_id);
        $this->assertSame('2026-08-13', $payment->tanggal_kwitansi->toDateString());
        $this->assertSame('lunas', $bill->fresh()->status);

        $this->from(route('tagihan.proses.siswa', $owner))->post(route('pembayaran.store'), $payload + [
            'siswa_id' => $owner->id,
        ])->assertRedirect()->assertSessionHasErrors('jumlah_bayar');
        $this->assertSame(1, Pembayaran::query()->where('tagihan_id', $bill->id)->count());
    }

    public function test_student_legacy_login_and_password_change_work_end_to_end(): void
    {
        $student = Siswa::query()->whereNotNull('password_raw')->where('password_raw', '<>', '')->firstOrFail();
        $oldPassword = $student->password_raw;

        $this->post(route('siswa.login.store'), [
            'username' => $student->username,
            'password' => $oldPassword,
        ])->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs($student, 'siswa');
        $this->assertTrue(Hash::check($oldPassword, $student->fresh()->password));

        $newPassword = 'Bb-' . Str::random(10);
        $this->put(route('siswa.profil.updatePassword'), [
            'current_password' => $oldPassword,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ])->assertRedirect(route('siswa.profil.index'))->assertSessionHas('success');

        $student->refresh();
        $this->assertTrue(Hash::check($newPassword, $student->password));
        $this->assertSame($newPassword, $student->password_raw);
    }

    public function test_edit_detail_ajax_document_and_excel_endpoints_do_not_return_server_errors(): void
    {
        $this->asAdmin();
        $school = Sekolah::query()->firstOrFail();
        $class = Kelas::query()->firstOrFail();
        $student = Siswa::query()->firstOrFail();
        $year = TahunAjaran::query()->firstOrFail();
        $admin = Admin::query()->firstOrFail();
        $type = JenisPembayaran::query()->first();
        $item = Koperasi::query()->first();

        $urls = [
            route('sekolah.show', $school), route('sekolah.edit', $school),
            route('kelas.edit', $class), route('siswa.show', $student), route('siswa.edit', $student),
            route('tahun_ajaran.edit', $year), route('admin.edit', $admin),
            route('siswa.kelas_by_sekolah', $school),
        ];
        if ($type) {
            $urls[] = route('jenis_pembayaran.edit', $type);
        }
        if ($item) {
            $urls[] = route('koperasi.edit', $item);
            $urls[] = route('koperasi.stok.edit', $item);
        }

        foreach ($urls as $url) {
            $response = $this->get($url);
            $this->assertTrue($response->isSuccessful(), "GET {$url} returned HTTP {$response->getStatusCode()}");
        }

        $this->get('/jenis-pembayaran/get-data-by-sekolah/' . $school->id)
            ->assertOk()->assertJsonStructure(['siswa', 'kelas']);
        if ($class->sekolah_id === $school->id) {
            $this->get(route('tagihan.students.summary', [$school->id, $class->id]))
                ->assertOk()->assertJsonStructure(['students']);
        }

        foreach ([
            'import.template', 'export_excel.download',
            'laporan.pembayaran.excel', 'laporan.pemasukan.excel', 'laporan.pengeluaran.excel',
            'laporan.siswa.excel', 'laporan.kelas.excel', 'laporan.sekolah.excel',
            'laporan.admin.excel', 'laporan.jenis_pembayaran.excel', 'laporan.kenaikan.excel',
            'laporan.kelulusan.excel', 'laporan.koperasi.excel', 'laporan.tahun_ajaran.excel',
        ] as $routeName) {
            $response = $this->get(route($routeName));
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirection(),
                "{$routeName} returned HTTP {$response->getStatusCode()}"
            );
        }
    }

    public function test_finance_crud_accepts_rupiah_input_and_removes_records(): void
    {
        $this->asAdmin();
        $school = Sekolah::query()->firstOrFail();

        $this->post(route('pemasukan.store'), [
            'sekolah_id' => $school->id,
            'tanggal' => '2026-08-13',
            'jumlah' => 'Rp 1.250.000',
            'sumber' => 'Black box',
        ])->assertRedirect(route('pemasukan.index'));
        $income = Pemasukan::query()->where('sumber', 'Black box')->firstOrFail();
        $this->assertEquals(1250000, (float) $income->jumlah);
        $this->get(route('pemasukan.show', $income))->assertOk();
        $this->delete(route('pemasukan.destroy', $income))->assertRedirect(route('pemasukan.index'));
        $this->assertDatabaseMissing('pemasukan', ['id' => $income->id]);

        $this->post(route('pengeluaran.store'), [
            'sekolah_id' => $school->id,
            'tanggal' => '2026-08-13',
            'jumlah' => 'Rp 750.000',
            'keperluan' => 'Black box',
        ])->assertRedirect(route('pengeluaran.index'));
        $expense = Pengeluaran::query()->where('keperluan', 'Black box')->firstOrFail();
        $this->assertEquals(750000, (float) $expense->jumlah);
        $this->delete(route('pengeluaran.destroy', $expense))->assertRedirect(route('pengeluaran.index'));
        $this->assertDatabaseMissing('pengeluaran', ['id' => $expense->id]);
    }

    public function test_current_database_has_no_duplicate_or_orphan_core_transactions(): void
    {
        $duplicateTypes = DB::table('jenis_pembayaran')
            ->select('sekolah_id', 'tahun_ajaran_id', 'nama_pembayaran')
            ->groupBy('sekolah_id', 'tahun_ajaran_id', 'nama_pembayaran')
            ->havingRaw('COUNT(*) > 1')->count();
        $duplicateBills = DB::table('tagihan')
            ->whereNotNull('jenis_pembayaran_id')
            ->select('siswa_id', 'jenis_pembayaran_id', 'periode')
            ->groupBy('siswa_id', 'jenis_pembayaran_id', 'periode')
            ->havingRaw('COUNT(*) > 1')->count();
        $duplicateTransactionBills = DB::table('pembayaran')
            ->whereNotNull('transaction_id')
            ->select('transaction_id', 'tagihan_id')
            ->groupBy('transaction_id', 'tagihan_id')
            ->havingRaw('COUNT(*) > 1')->count();

        $orphanBills = DB::table('tagihan')->leftJoin('siswa', 'tagihan.siswa_id', '=', 'siswa.id')
            ->whereNull('siswa.id')->count();
        $orphanPayments = DB::table('pembayaran')
            ->leftJoin('siswa', 'pembayaran.siswa_id', '=', 'siswa.id')
            ->leftJoin('tagihan', 'pembayaran.tagihan_id', '=', 'tagihan.id')
            ->where(fn ($query) => $query->whereNull('siswa.id')->orWhereNull('tagihan.id'))->count();
        $orphanSaleDetails = DB::table('koperasi_penjualan_detail')
            ->leftJoin('koperasi_penjualan', 'koperasi_penjualan_detail.koperasi_penjualan_id', '=', 'koperasi_penjualan.id')
            ->leftJoin('koperasi', 'koperasi_penjualan_detail.koperasi_id', '=', 'koperasi.id')
            ->where(fn ($query) => $query->whereNull('koperasi_penjualan.id')->orWhereNull('koperasi.id'))->count();

        $paymentCoverage = DB::table('pembayaran')
            ->select('tagihan_id')
            ->selectRaw('SUM(jumlah_bayar + COALESCE(diskon, 0)) AS paid')
            ->groupBy('tagihan_id');
        $overpaidBills = DB::table('tagihan as tagihan')
            ->leftJoinSub(clone $paymentCoverage, 'coverage', 'coverage.tagihan_id', '=', 'tagihan.id')
            ->whereRaw('COALESCE(coverage.paid, 0) > tagihan.nominal + 0.01')
            ->count();
        $incorrectPaidStatus = DB::table('tagihan as tagihan')
            ->leftJoinSub(clone $paymentCoverage, 'coverage', 'coverage.tagihan_id', '=', 'tagihan.id')
            ->where('tagihan.status', 'lunas')
            ->whereRaw('COALESCE(coverage.paid, 0) + 0.01 < tagihan.nominal')
            ->count();
        $incorrectUnpaidStatus = DB::table('tagihan as tagihan')
            ->leftJoinSub(clone $paymentCoverage, 'coverage', 'coverage.tagihan_id', '=', 'tagihan.id')
            ->where('tagihan.status', '<>', 'lunas')
            ->whereRaw('COALESCE(coverage.paid, 0) >= tagihan.nominal')
            ->count();
        $studentClassSchoolMismatch = DB::table('siswa as siswa')
            ->join('kelas as kelas', 'siswa.kelas_id', '=', 'kelas.id')
            ->whereColumn('siswa.id_sekolah', '<>', 'kelas.sekolah_id')
            ->count();
        $paymentStudentBillMismatch = DB::table('pembayaran as pembayaran')
            ->join('tagihan as tagihan', 'pembayaran.tagihan_id', '=', 'tagihan.id')
            ->whereColumn('pembayaran.siswa_id', '<>', 'tagihan.siswa_id')
            ->count();
        $negativeStock = DB::table('koperasi')->where('stok', '<', 0)->count();
        $saleDetailTotals = DB::table('koperasi_penjualan_detail')
            ->select('koperasi_penjualan_id')
            ->selectRaw('SUM(subtotal) AS detail_total')
            ->groupBy('koperasi_penjualan_id');
        $saleTotalMismatch = DB::table('koperasi_penjualan as penjualan')
            ->leftJoinSub($saleDetailTotals, 'details', 'details.koperasi_penjualan_id', '=', 'penjualan.id')
            ->whereRaw('ABS(COALESCE(details.detail_total, 0) - penjualan.total) > 0.01')
            ->count();

        $this->assertSame(0, $duplicateTypes, 'Ada jenis pembayaran ganda.');
        $this->assertSame(0, $duplicateBills, 'Ada tagihan siswa/periode ganda.');
        $this->assertSame(0, $duplicateTransactionBills, 'Satu transaksi memuat tagihan yang sama lebih dari sekali.');
        $this->assertSame(0, $orphanBills, 'Ada tagihan tanpa siswa.');
        $this->assertSame(0, $orphanPayments, 'Ada pembayaran tanpa siswa atau tagihan.');
        $this->assertSame(0, $orphanSaleDetails, 'Ada detail penjualan koperasi tanpa induk atau barang.');
        $this->assertSame(0, $overpaidBills, 'Ada pembayaran yang melebihi nominal tagihan.');
        $this->assertSame(0, $incorrectPaidStatus, 'Ada status lunas tanpa pembayaran yang mencukupi.');
        $this->assertSame(0, $incorrectUnpaidStatus, 'Ada tagihan terbayar penuh yang belum berstatus lunas.');
        $this->assertSame(0, $studentClassSchoolMismatch, 'Ada siswa yang kelasnya berasal dari sekolah lain.');
        $this->assertSame(0, $paymentStudentBillMismatch, 'Ada pembayaran yang siswanya berbeda dari pemilik tagihan.');
        $this->assertSame(0, $negativeStock, 'Ada stok koperasi negatif.');
        $this->assertSame(0, $saleTotalMismatch, 'Ada total penjualan koperasi yang berbeda dari rincian barang.');
    }
}
