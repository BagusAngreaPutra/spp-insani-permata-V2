<?php

namespace Tests\Feature;

use App\Models\{Admin, JenisPembayaran, Kelas, Koperasi, KoperasiPenjualan, Pembayaran, Pemasukan, Pengeluaran, ReceiptSetting, Sekolah, Siswa, Tagihan, TahunAjaran};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TransactionRevisionTest extends TestCase
{
    use RefreshDatabase;

    private Admin $officer;
    private Sekolah $school;
    private Siswa $student;
    private Tagihan $bill;
    private Koperasi $good;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 14));
        $this->officer = Admin::create(['nama_admin' => 'Petugas Input Asli', 'username' => 'input-test', 'password' => 'password', 'role' => 'super_admin']);
        $this->actingAs($this->officer, 'web');
        $this->school = Sekolah::create(['nama_sekolah' => 'Sekolah Uji', 'kode_sekolah' => 'SDIT', 'alamat' => 'Jambi', 'kontak' => 'Admin']);
        $year = TahunAjaran::create(['nama_tahun' => '2026/2027', 'aktif' => true]);
        $class = Kelas::create(['sekolah_id' => $this->school->id, 'tahun_ajaran_id' => $year->id, 'nama_kelas' => 'A', 'tingkat' => 1]);
        $this->student = Siswa::create(['id_sekolah' => $this->school->id, 'tahun_ajaran_id' => $year->id, 'kelas_id' => $class->id, 'nis' => 'REV-1', 'nama' => 'Siswa Uji', 'username' => 'siswa-revisi', 'password' => 'password', 'status' => 'aktif']);
        $type = JenisPembayaran::create(['sekolah_id' => $this->school->id, 'tahun_ajaran_id' => $year->id, 'nama_pembayaran' => 'Karate', 'tipe' => 'bulanan', 'nominal' => 100000, 'jatuh_tempo' => '2026-08-10', 'target_type' => 'all']);
        $this->bill = Tagihan::create(['siswa_id' => $this->student->id, 'id_sekolah' => $this->school->id, 'jenis_pembayaran_id' => $type->id, 'tahun_ajaran_id' => $year->id, 'nama_tagihan' => 'Karate - Tahun Ajaran 2026/2027', 'nominal' => 100000, 'tipe' => 'bulanan', 'periode' => '2026-08', 'tanggal_jatuh_tempo' => '2026-08-10', 'status' => 'belum']);
        $this->good = Koperasi::create(['sekolah_id' => $this->school->id, 'nama_barang' => 'Buku Uji', 'harga_jual' => 20000, 'stok' => 10, 'status' => 'aktif', 'jenis_transaksi' => 'jual']);
    }

    private function payment(array $extra = []): Pembayaran
    {
        return Pembayaran::create(array_merge(['siswa_id' => $this->student->id, 'tagihan_id' => $this->bill->id, 'sekolah_id' => $this->school->id, 'jumlah_bayar' => 100000, 'tanggal_bayar' => '2026-10-14', 'periode' => '2026-08', 'metode_bayar' => 'tunai'], $extra));
    }

    private function saleData(int $quantity = 2): array
    {
        return ['siswa_id' => $this->student->id, 'tanggal' => '2026-10-14', 'metode_bayar' => 'transfer', 'catatan' => 'Transfer wali siswa', 'items' => [['koperasi_id' => $this->good->id, 'jumlah' => $quantity]]];
    }

    private function sale(): KoperasiPenjualan
    {
        $this->post(route('koperasi.penjualan.store'), $this->saleData())->assertSessionHasNoErrors();
        return KoperasiPenjualan::latest('id')->firstOrFail();
    }

    public function test_extracurricular_period_and_optional_original_officer_on_html_receipt(): void
    {
        $payment = $this->payment();
        $viewer = Admin::create(['nama_admin' => 'Petugas Cetak Lain', 'username' => 'viewer-test', 'password' => 'password', 'role' => 'super_admin']);
        $this->actingAs($viewer, 'web');
        $this->get(route('pembayaran.kwitansi', $payment))->assertOk()->assertSee('Karate AGUSTUS 2026')->assertDontSee('Petugas Input Asli');
        ReceiptSetting::create(['id' => 1, 'show_officer' => true, 'numbering_period' => 'yearly']);
        $response = $this->get(route('pembayaran.kwitansi', $payment))->assertOk();
        $response->assertSee('receipt-signature-line">Petugas Input Asli', false);
        $response->assertDontSee('receipt-signature-line">Petugas Cetak Lain', false);
    }

    public function test_cancellation_cancels_whole_group_reopens_bill_and_does_not_reuse_number(): void
    {
        $first = $this->payment(['jumlah_bayar' => 60000, 'transaction_id' => 'GROUP-TEST']);
        $second = $this->payment(['jumlah_bayar' => 40000, 'transaction_id' => 'GROUP-TEST']);
        $this->bill->update(['status' => 'lunas']);
        $this->delete(route('pembayaran.destroy', $first), ['alasan_pembatalan' => 'Pembayaran salah input'])->assertRedirect(route('riwayat.index'));
        $this->assertSoftDeleted($first); $this->assertSoftDeleted($second);
        $this->assertSame('belum', $this->bill->fresh()->status);
        $this->assertEquals(0, Pembayaran::sum('jumlah_bayar'));
        $this->assertSame('Pembayaran salah input', $first->fresh()->cancellation_reason);
        $this->get(route('pembayaran.kwitansi', $first))->assertNotFound();
        $new = $this->payment();
        $this->assertNotSame($first->nomor_kwitansi, $new->nomor_kwitansi);
        $this->delete(route('pembayaran.destroy', $first), ['alasan_pembatalan' => 'Ulang pembatalan'])->assertNotFound();
    }

    public function test_nominal_correction_updates_bill_status_and_rejects_overpayment(): void
    {
        $payment = $this->payment();
        $data = ['siswa_id' => $this->student->id, 'tanggal_bayar' => '2026-10-14', 'tanggal_kwitansi' => '2026-10-14', 'metode_bayar' => 'transfer', 'alasan_koreksi' => 'Nominal salah input', 'amounts' => [$payment->id => 50000]];
        $this->put(route('pembayaran.koreksi.update', $payment), $data)->assertSessionHasNoErrors();
        $this->assertEquals(50000, $payment->fresh()->jumlah_bayar);
        $this->assertSame('belum', $this->bill->fresh()->status);
        $data['amounts'][$payment->id] = 100001;
        $this->put(route('pembayaran.koreksi.update', $payment), $data)->assertSessionHasErrors();
        $this->assertEquals(50000, $payment->fresh()->jumlah_bayar);
    }

    public function test_cooperative_correction_preserves_prices_updates_stock_and_cancellation_returns_stock_once(): void
    {
        $sale = $this->sale();
        $this->assertSame(8, $this->good->fresh()->stok);
        $this->get(route('koperasi.penjualan.kwitansi', $sale))->assertOk()->assertSee('Transfer (TF)')->assertSee('Koreksi transaksi')->assertSee('Batalkan / Hapus transaksi');
        $this->good->update(['harga_jual' => 25000]);
        $data = $this->saleData(3) + ['alasan_koreksi' => 'Jumlah buku seharusnya tiga'];
        $data['metode_bayar'] = 'tunai';
        $this->get(route('koperasi.penjualan.edit', $sale))->assertOk()->assertSee('Koreksi '.$sale->kode_transaksi);
        $this->put(route('koperasi.penjualan.update', $sale), $data)->assertSessionHasNoErrors();
        $this->assertSame(7, $this->good->fresh()->stok);
        $this->assertEquals(60000, $sale->fresh()->total);
        $this->assertSame('tunai', $sale->fresh()->metode_bayar);
        $this->delete(route('koperasi.penjualan.destroy', $sale), ['alasan_pembatalan' => 'Siswa membatalkan pembelian'])->assertSessionHasNoErrors();
        $this->assertSame(10, $this->good->fresh()->stok);
        $this->assertSoftDeleted($sale);
        $this->assertEquals(0, KoperasiPenjualan::sum('total'));
        $this->delete(route('koperasi.penjualan.destroy', $sale), ['alasan_pembatalan' => 'Ulang pembatalan'])->assertNotFound();
        $this->assertSame(10, $this->good->fresh()->stok);
    }

    public function test_invalid_cooperative_correction_rolls_back_stock_and_details(): void
    {
        $sale = $this->sale();
        $this->put(route('koperasi.penjualan.update', $sale), $this->saleData(11) + ['alasan_koreksi' => 'Koreksi jumlah barang'])->assertSessionHasErrors('items');
        $this->assertSame(8, $this->good->fresh()->stok);
        $this->assertEquals(40000, $sale->fresh()->total);
        $this->assertEquals(2, $sale->details()->sum('jumlah'));
    }

    public function test_duplicate_item_rows_cannot_oversell_stock(): void
    {
        $data = $this->saleData(6);
        $data['items'][] = ['koperasi_id' => $this->good->id, 'jumlah' => 6];
        $this->post(route('koperasi.penjualan.store'), $data)->assertSessionHasErrors('items');
        $this->assertSame(10, $this->good->fresh()->stok);
        $this->assertSame(0, KoperasiPenjualan::count());
    }

    public function test_yearly_and_monthly_sequences_reset_at_the_chosen_boundary(): void
    {
        $this->assertSame('000001/SDIT/2026', $this->payment()->nomor_kwitansi);
        $this->travelTo(now()->setDate(2026, 11, 1));
        $this->assertSame('000002/SDIT/2026', $this->payment()->nomor_kwitansi);
        ReceiptSetting::create(['id' => 1, 'numbering_period' => 'monthly']);
        $this->assertSame('000001/SDIT/2026/11', $this->payment()->nomor_kwitansi);
        $this->assertSame('000002/SDIT/2026/11', $this->payment()->nomor_kwitansi);
        $this->travelTo(now()->setDate(2026, 12, 1));
        $this->assertSame('000001/SDIT/2026/12', $this->payment()->nomor_kwitansi);
        $this->assertStringStartsWith('KOP-202612-', $this->sale()->kode_transaksi);
        ReceiptSetting::find(1)->update(['numbering_period' => 'yearly']);
        $this->assertSame('000003/SDIT/2026', $this->payment()->nomor_kwitansi);
        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->assertSame('000001/SDIT/2027', $this->payment()->nomor_kwitansi);
    }

    public function test_report_funds_match_cash_received_and_excel_contains_month_without_cohort(): void
    {
        $this->bill->jenisPembayaran->update(['nama_pembayaran' => 'SPP SD Angkatan 2026/2027']);
        $this->bill->update(['nama_tagihan' => 'SPP SD Angkatan 2026/2027 - Tahun Ajaran 2026/2027']);
        foreach (['tunai', 'transfer', 'kjc', 'tabungan'] as $method) { $this->payment(['jumlah_bayar' => 10000, 'metode_bayar' => $method]); }
        $this->sale();
        Pemasukan::create(['sekolah_id' => $this->school->id, 'tanggal' => '2026-10-14', 'jumlah' => 5000, 'sumber' => 'Donasi']);
        $response = $this->get(route('laporan.transaksi'))->assertOk()->assertSee('SPP AGUSTUS - Tahun Ajaran 2026/2027')->assertDontSee('Angkatan');
        $funds = $response->viewData('summary')['funds'];
        $this->assertSame(['tunai' => 10000.0, 'transfer' => 50000.0, 'kjc' => 10000.0, 'tabungan' => 10000.0, 'belum' => 5000.0], $funds);
        $this->assertEquals($response->viewData('summary')['received'], array_sum($funds));
        $this->get(route('laporan.tagihan_siswa'))->assertOk()->assertSee('SPP AGUSTUS - Tahun Ajaran 2026/2027')->assertDontSee('Angkatan');
        $export = $this->get(route('laporan.transaksi.excel'))->assertOk();
        $workbook = IOFactory::load($export->baseResponse->getFile()->getPathname());
        $sheet = $workbook->getActiveSheet();
        $this->assertSame('I', $sheet->getHighestColumn());
        $fundSheet = $workbook->getSheetByName('Jenis Dana');
        $this->assertSame('TN / Tunai', $fundSheet->getCell('D4')->getValue());
        $this->assertSame('Belum dicatat', $fundSheet->getCell('H4')->getValue());
        $this->assertEquals(10000, $fundSheet->getCell('D11')->getValue());
        $this->assertEquals(50000, $fundSheet->getCell('E11')->getValue());
        $this->assertSame('landscape', $sheet->getPageSetup()->getOrientation());
        $this->assertStringNotContainsString('Angkatan', json_encode($sheet->toArray()));
    }

    public function test_cash_out_notes_and_cash_summary_are_accessible(): void
    {
        Pengeluaran::create(['sekolah_id' => $this->school->id, 'tanggal' => '2026-10-14', 'jumlah' => 25000, 'keperluan' => 'ATK', 'keterangan' => 'Nota belanja tersimpan']);
        $this->get(route('pengeluaran.index'))->assertOk()->assertSee('Nota belanja tersimpan')->assertSee('Catatan Kas Keluar');
        $this->payment();
        $response = $this->get(route('keuangan.kas.index'))->assertOk();
        $this->assertEquals(75000, $response->viewData('data')->first()['saldo']);
    }

    public function test_mutations_require_permission_and_cancellation_reason(): void
    {
        $payment = $this->payment();
        $this->delete(route('pembayaran.destroy', $payment))->assertSessionHasErrors('alasan_pembatalan');
        $this->assertFalse($payment->fresh()->trashed());
        $limited = Admin::create(['nama_admin' => 'Tanpa Izin', 'username' => 'limited-test', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($limited, 'web');
        $this->delete(route('pembayaran.destroy', $payment), ['alasan_pembatalan' => 'Coba tanpa izin'])->assertForbidden();
        $this->get(route('pengaturan.kwitansi.edit'))->assertForbidden();
        $this->assertFalse($payment->fresh()->trashed());
    }

    public function test_receipt_settings_can_be_changed_without_renumbering_existing_receipts(): void
    {
        $payment = $this->payment();
        $oldNumber = $payment->nomor_kwitansi;
        $this->get(route('pengaturan.kwitansi.edit'))->assertOk()->assertSee('Urutan nomor kwitansi');
        $this->put(route('pengaturan.kwitansi.update'), ['numbering_period' => 'monthly', 'show_officer' => 1])->assertSessionHasNoErrors();
        $this->assertSame('monthly', ReceiptSetting::current()->numbering_period);
        $this->assertTrue(ReceiptSetting::current()->show_officer);
        $this->assertSame($oldNumber, $payment->fresh()->nomor_kwitansi);
    }
}
