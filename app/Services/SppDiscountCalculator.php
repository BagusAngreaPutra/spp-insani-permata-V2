<?php

namespace App\Services;

use App\Models\SppDiscountSetting;
use App\Models\Tagihan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SppDiscountCalculator
{
    public const MAX_DISCOUNT = 25000;

    public function configuration(): array
    {
        $default = [
            'amount' => (float) self::MAX_DISCOUNT,
            'valid_from' => null,
            'valid_until' => null,
            'active' => true,
            'custom_period' => false,
        ];

        try {
            if (! Schema::hasTable('spp_discount_settings')) {
                return $default;
            }

            $setting = SppDiscountSetting::query()->first();

            if (! $setting) {
                return $default;
            }

            $validFrom = $setting->berlaku_mulai?->toDateString();
            $validUntil = $setting->berlaku_sampai?->toDateString();

            return [
                'amount' => (float) $setting->nominal,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'active' => (bool) $setting->aktif,
                'custom_period' => $validFrom !== null && $validUntil !== null,
            ];
        } catch (Throwable) {
            return $default;
        }
    }

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

        $configuration = $this->configuration();

        if (! $configuration['active'] || $configuration['amount'] <= 0) {
            return 0;
        }

        $paidAt = $paymentDate instanceof CarbonInterface
            ? Carbon::instance($paymentDate)->startOfDay()
            : Carbon::parse($paymentDate)->startOfDay();

        if ($configuration['custom_period']) {
            $validFrom = Carbon::parse($configuration['valid_from'])->startOfDay();
            $validUntil = Carbon::parse($configuration['valid_until'])->startOfDay();

            if ($paidAt->lessThan($validFrom) || $paidAt->greaterThan($validUntil)) {
                return 0;
            }
        } else {
            $deadline = $this->discountDeadline($bill);

            if (! $deadline || $paidAt->greaterThan($deadline)) {
                return 0;
            }
        }

        $remaining = max(0, (float) $bill->nominal - $existingCoverage);
        $discount = min((float) $configuration['amount'], $remaining);

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
