<?php

namespace App\Services;

use App\Models\KoperasiPenjualan;
use App\Models\Pembayaran;
use App\Models\ReceiptSetting;
use App\Models\Sekolah;
use Illuminate\Support\Facades\DB;

class ReceiptNumberGenerator
{
    public function school(Sekolah $school): string
    {
        $period = now()->format(ReceiptSetting::current()->numbering_period === 'monthly' ? 'Y/m' : 'Y');
        $suffix = '/'.$school->kode_sekolah.'/'.$period;
        return DB::transaction(function () use ($school, $period, $suffix) {
            $existing = Pembayaran::withTrashed()->where('nomor_kwitansi', 'like', '%'.$suffix)
                ->pluck('nomor_kwitansi')->map(fn ($number) => (int) explode('/', $number)[0])->max() ?? 0;
            $number = $this->next('school:'.$school->id.':'.$period, $existing);
            return sprintf('%06d%s', $number, $suffix);
        });
    }

    public function cooperative(): string
    {
        $period = now()->format(ReceiptSetting::current()->numbering_period === 'monthly' ? 'Ym' : 'Y');
        $prefix = 'KOP-'.$period.'-';
        return DB::transaction(function () use ($period, $prefix) {
            $existing = KoperasiPenjualan::withTrashed()->where('kode_transaksi', 'like', $prefix.'%')
                ->pluck('kode_transaksi')->map(fn ($number) => (int) substr($number, strlen($prefix)))->max() ?? 0;
            return $prefix.str_pad((string) $this->next('cooperative:'.$period, $existing), 6, '0', STR_PAD_LEFT);
        });
    }

    private function next(string $scope, int $existing): int
    {
        DB::table('receipt_sequences')->insertOrIgnore(['scope' => $scope, 'last_number' => $existing]);
        $sequence = DB::table('receipt_sequences')->where('scope', $scope)->lockForUpdate()->first();
        $number = max((int) $sequence->last_number, $existing) + 1;
        DB::table('receipt_sequences')->where('scope', $scope)->update(['last_number' => $number]);
        return $number;
    }
}
