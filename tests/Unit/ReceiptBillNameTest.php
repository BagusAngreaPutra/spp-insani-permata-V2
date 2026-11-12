<?php

namespace Tests\Unit;

use App\Http\Controllers\PembayaranController;
use App\Models\JenisPembayaran;
use App\Models\Tagihan;
use App\Models\TahunAjaran;
use ReflectionMethod;
use Tests\TestCase;

class ReceiptBillNameTest extends TestCase
{
    public function test_spp_receipt_uses_only_the_payment_month(): void
    {
        $bill = new Tagihan([
            'nama_tagihan' => 'SPP SD Angkatan 2026/2027 - Tahun Ajaran 2026/2027',
            'periode' => '2026-08',
        ]);
        $bill->setRelation('jenisPembayaran', new JenisPembayaran([
            'nama_pembayaran' => 'SPP SD Angkatan 2026/2027',
        ]));

        $this->assertSame('SPP AGUSTUS', $this->receiptName($bill));
    }

    public function test_activity_school_receipt_uses_only_the_academic_year(): void
    {
        $academicYear = new TahunAjaran(['nama_tahun' => '2026/2027']);
        $paymentType = new JenisPembayaran([
            'nama_pembayaran' => 'Activity School SD 2026/2027',
        ]);
        $paymentType->setRelation('tahunAjaran', $academicYear);

        $bill = new Tagihan([
            'nama_tagihan' => 'Activity School SD 2026/2027 - Tahun Ajaran 2026/2027',
            'periode' => '2026',
        ]);
        $bill->setRelation('jenisPembayaran', $paymentType);
        $bill->setRelation('tahunAjaran', $academicYear);

        $this->assertSame(
            'Activity School - Tahun Ajaran 2026/2027',
            $this->receiptName($bill)
        );
    }

    private function receiptName(Tagihan $bill): string
    {
        $method = new ReflectionMethod(PembayaranController::class, 'receiptBillName');
        $method->setAccessible(true);

        return $method->invoke(app(PembayaranController::class), $bill);
    }

    public function test_karate_and_tpq_receipts_include_the_bill_month_and_year(): void
    {
        foreach (['Karate', 'TPQ'] as $name) {
            $bill = new Tagihan(['nama_tagihan' => $name.' - Tahun Ajaran 2026/2027', 'tipe' => 'bulanan', 'periode' => '2026-09']);
            $bill->setRelation('jenisPembayaran', new JenisPembayaran(['nama_pembayaran' => $name]));
            $this->assertSame($name.' SEPTEMBER 2026', $this->receiptName($bill));
        }
    }
}
