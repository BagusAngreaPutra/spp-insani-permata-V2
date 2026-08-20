<?php

namespace Tests\Unit;

use App\Models\JenisPembayaran;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use App\Services\TagihanPeriodReconciler;
use PHPUnit\Framework\TestCase;

class TagihanPeriodReconcilerTest extends TestCase
{
    public function test_legacy_one_time_bill_in_the_same_academic_year_remains_visible(): void
    {
        $academicYear = new TahunAjaran(['nama_tahun' => '2026/2027']);
        $academicYear->id = 5;

        $paymentType = new JenisPembayaran([
            'tahun_ajaran_id' => 5,
            'nama_pembayaran' => 'Buku Paket Kelas 2000',
            'tipe' => 'sekali',
        ]);
        $paymentType->setRelation('tahunAjaran', $academicYear);

        $bill = new Tagihan([
            'jenis_pembayaran_id' => 76,
            'tahun_ajaran_id' => 5,
            'nama_tagihan' => 'Buku Paket Kelas 2 - Tahun Ajaran 2026/2027',
            'tipe' => 'sekali',
            'periode' => '2026-07',
        ]);
        $bill->setRelation('jenisPembayaran', $paymentType);

        $this->assertTrue((new TagihanPeriodReconciler())->belongsToConfiguredPeriod($bill));
        $this->assertSame(
            'Buku Paket Kelas 2000 - Tahun Ajaran 2026/2027',
            $bill->nama_tagihan_dinamis
        );
    }

    public function test_one_time_bill_from_another_academic_year_stays_hidden(): void
    {
        $academicYear = new TahunAjaran(['nama_tahun' => '2026/2027']);
        $academicYear->id = 5;

        $paymentType = new JenisPembayaran([
            'tahun_ajaran_id' => 5,
            'nama_pembayaran' => 'Buku Paket Kelas 2000',
            'tipe' => 'sekali',
        ]);
        $paymentType->setRelation('tahunAjaran', $academicYear);

        $bill = new Tagihan([
            'tahun_ajaran_id' => 4,
            'tipe' => 'sekali',
            'periode' => '2025-07',
        ]);
        $bill->setRelation('jenisPembayaran', $paymentType);

        $this->assertFalse((new TagihanPeriodReconciler())->belongsToConfiguredPeriod($bill));
    }
}
