<?php

namespace Tests\Unit;

use App\Models\JenisPembayaran;
use App\Models\Tagihan;
use App\Services\SppDiscountCalculator;
use Tests\TestCase;

class SppDiscountCalculatorTest extends TestCase
{
    private SppDiscountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new SppDiscountCalculator();
    }

    public function test_full_spp_settlement_on_or_before_due_date_gets_discount(): void
    {
        $bill = $this->sppBill();

        $this->assertSame(
            25000.0,
            $this->calculator->settlementDiscount($bill, '2026-08-10', 150000)
        );
    }

    public function test_payment_after_due_date_does_not_get_discount(): void
    {
        $bill = $this->sppBill();

        $this->assertSame(
            0.0,
            $this->calculator->settlementDiscount($bill, '2026-08-11', 150000)
        );
    }

    public function test_partial_payment_does_not_get_discount(): void
    {
        $bill = $this->sppBill();

        $this->assertSame(
            0.0,
            $this->calculator->settlementDiscount($bill, '2026-08-09', 100000)
        );
    }

    public function test_non_spp_bill_never_gets_spp_discount(): void
    {
        $bill = new Tagihan([
            'nama_tagihan' => 'Buku Paket',
            'nominal' => 175000,
            'periode' => '2026-08',
            'tanggal_jatuh_tempo' => '2026-08-10',
        ]);

        $this->assertSame(
            0.0,
            $this->calculator->settlementDiscount($bill, '2026-08-09', 150000)
        );
    }

    private function sppBill(): Tagihan
    {
        $bill = new Tagihan([
            'nama_tagihan' => 'SPP PAUD - Tahun Ajaran 2026/2027',
            'nominal' => 175000,
            'periode' => '2026-08',
            'tanggal_jatuh_tempo' => '2026-08-10',
        ]);
        $bill->setRelation('jenisPembayaran', new JenisPembayaran([
            'nama_pembayaran' => 'SPP PAUD',
        ]));

        return $bill;
    }
}
