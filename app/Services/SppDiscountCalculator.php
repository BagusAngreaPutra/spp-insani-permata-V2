<?php

namespace App\Services;

use App\Models\Tagihan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SppDiscountCalculator
{
    public const MAX_DISCOUNT = 25000;

    public function isSpp(Tagihan $bill): bool
    {
        $name = trim((string) ($bill->jenisPembayaran?->nama_pembayaran ?: $bill->nama_tagihan));

        return preg_match('/\bspp\b/i', $name) === 1;
    }

    public function settlementDiscount(
        Tagihan $bill,
        CarbonInterface|string $paymentDate,
        float $cashAmount,
        float $existingCoverage = 0
    ): float {
        if (! $this->isSpp($bill)) {
            return 0;
        }

        $deadline = $this->discountDeadline($bill);
        $paidAt = $paymentDate instanceof CarbonInterface
            ? Carbon::instance($paymentDate)->startOfDay()
            : Carbon::parse($paymentDate)->startOfDay();

        if (! $deadline || $paidAt->greaterThan($deadline)) {
            return 0;
        }

        $remaining = max(0, (float) $bill->nominal - $existingCoverage);
        $discount = min((float) self::MAX_DISCOUNT, $remaining);

        return abs(($cashAmount + $discount) - $remaining) <= 0.01
            ? $discount
            : 0;
    }

    private function discountDeadline(Tagihan $bill): ?Carbon
    {
        if ($bill->tanggal_jatuh_tempo) {
            return Carbon::parse($bill->tanggal_jatuh_tempo)->startOfDay();
        }

        $period = trim((string) $bill->periode);

        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches) !== 1) {
            return null;
        }

        return Carbon::create((int) $matches[1], (int) $matches[2], 10)->startOfDay();
    }
}
