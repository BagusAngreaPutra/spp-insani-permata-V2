<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\JenisPembayaran;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCorrectionDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_correcting_spp_date_recalculates_discount_and_bill_status(): void
    {
        $admin = Admin::create([
            'nama_admin' => 'Administrator Test',
            'username' => 'admin-koreksi-test',
            'password' => 'password',
            'role' => 'super_admin',
        ]);
        $school = Sekolah::create([
            'nama_sekolah' => 'TK IT Permata Insani',
            'kode_sekolah' => 'TKIT',
            'alamat' => 'Jambi',
            'kontak' => 'Administrasi',
        ]);
        $academicYear = TahunAjaran::create([
            'nama_tahun' => '2026/2027',
            'aktif' => true,
        ]);
        $class = Kelas::create([
            'sekolah_id' => $school->id,
            'tahun_ajaran_id' => $academicYear->id,
            'nama_kelas' => 'TK B',
            'tingkat' => null,
        ]);
        $student = Siswa::create([
            'id_sekolah' => $school->id,
            'tahun_ajaran_id' => $academicYear->id,
            'kelas_id' => $class->id,
            'nis' => 'NISA-TEST',
            'nama' => 'Nisa Rizqia',
            'username' => 'nisa-rizqia-test',
            'password' => 'password',
            'status' => 'aktif',
            'nominal_spp' => 175000,
        ]);
        $paymentType = JenisPembayaran::create([
            'sekolah_id' => $school->id,
            'tahun_ajaran_id' => $academicYear->id,
            'nama_pembayaran' => 'SPP PAUD',
            'tipe' => 'bulanan',
            'nominal' => 175000,
            'jatuh_tempo' => '2026-07-10',
            'target_type' => 'specific_students',
        ]);
        $bill = Tagihan::create([
            'siswa_id' => $student->id,
            'id_sekolah' => $school->id,
            'jenis_pembayaran_id' => $paymentType->id,
            'tahun_ajaran_id' => $academicYear->id,
            'nama_tagihan' => 'SPP PAUD - Tahun Ajaran 2026/2027',
            'nominal' => 175000,
            'tipe' => 'bulanan',
            'periode' => '2026-08',
            'tanggal_jatuh_tempo' => '2026-08-10',
            'status' => 'belum',
        ]);
        $payment = Pembayaran::create([
            'transaction_id' => 'koreksi-spp-test',
            'nomor_kwitansi' => '000001/TKIT/2026',
            'sekolah_id' => $school->id,
            'tagihan_id' => $bill->id,
            'siswa_id' => $student->id,
            'jumlah_bayar' => 150000,
            'diskon' => 0,
            'tanggal_bayar' => '2026-08-11',
            'tanggal_kwitansi' => '2026-08-11',
            'periode' => '2026-08',
            'metode_bayar' => 'tunai',
            'keterangan' => 'Pembayaran SPP Agustus',
        ]);

        $this->actingAs($admin, 'web')
            ->put(route('pembayaran.koreksi.update', $payment), [
                'siswa_id' => $student->id,
                'tanggal_bayar' => '2026-08-09',
                'tanggal_kwitansi' => '2026-08-11',
                'metode_bayar' => 'tunai',
                'keterangan' => 'Pembayaran SPP Agustus',
                'alasan_koreksi' => 'Tanggal pembayaran sebelumnya salah input.',
            ])
            ->assertRedirect(route('riwayat.index'))
            ->assertSessionHas('success');

        $this->assertSame(25000.0, (float) $payment->fresh()->diskon);
        $this->assertSame('lunas', $bill->fresh()->status);

        $download = $this->get(route('pembayaran.kwitansi.pdf', $payment));
        $download->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $download->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $download->headers->get('content-disposition'));

        $preview = $this->get(route('pembayaran.kwitansi.pdf', [
            'id' => $payment->id,
            'preview' => 1,
        ]));
        $preview->assertOk();
        $this->assertStringContainsString('inline', (string) $preview->headers->get('content-disposition'));

        $groupDownload = $this->get(route('pembayaran.kwitansi.grup.pdf', [
            'ids' => (string) $payment->id,
        ]));
        $groupDownload->assertOk();
        $this->assertStringContainsString('attachment', (string) $groupDownload->headers->get('content-disposition'));

        $this->actingAs($admin, 'web')
            ->put(route('pembayaran.koreksi.update', $payment), [
                'siswa_id' => $student->id,
                'tanggal_bayar' => '2026-08-11',
                'tanggal_kwitansi' => '2026-08-11',
                'metode_bayar' => 'tunai',
                'keterangan' => 'Pembayaran SPP Agustus',
                'alasan_koreksi' => 'Tanggal pembayaran dikembalikan setelah jatuh tempo.',
            ])
            ->assertRedirect(route('riwayat.index'))
            ->assertSessionHas('success');

        $this->assertSame(0.0, (float) $payment->fresh()->diskon);
        $this->assertSame('belum', $bill->fresh()->status);
    }
}
