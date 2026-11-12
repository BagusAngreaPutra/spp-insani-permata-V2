<?php

namespace App\Services;

use App\Models\KoperasiPenjualan;
use App\Models\WalletAccount;
use App\Models\WalletPayment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletCooperativePaymentService
{
    public function __construct(private readonly WalletLedgerService $ledger) {}

    public function pay(KoperasiPenjualan $sale, ?string $idempotencyKey = null): WalletPayment
    {
        return DB::transaction(function () use ($sale, $idempotencyKey): WalletPayment {
            $wallet = WalletAccount::where('siswa_id', $sale->siswa_id)->lockForUpdate()->first();
            if (!$wallet) {
                throw new RuntimeException('Siswa belum memiliki wallet.');
            }

            $existing = WalletPayment::where('source_type', 'koperasi_penjualan')
                ->where('source_id', $sale->getKey())
                ->where('status', 'paid')
                ->first();
            if ($existing) {
                return $existing;
            }

            $journal = $this->ledger->debit(
                $wallet,
                $sale->total,
                'cooperative_payment',
                'cooperative_revenue',
                $idempotencyKey ?: 'cooperative-sale:'.$sale->getKey(),
                ['sale_id' => $sale->getKey(), 'sale_code' => $sale->kode_transaksi],
                'Pembayaran koperasi melalui dompet siswa',
            );

            return WalletPayment::create([
                'wallet_account_id' => $wallet->getKey(),
                'wallet_journal_id' => $journal->getKey(),
                'source_type' => 'koperasi_penjualan',
                'source_id' => $sale->getKey(),
                'amount' => $sale->total,
                'status' => 'paid',
                'idempotency_key' => 'cooperative-payment:'.$sale->getKey(),
                'paid_at' => now(),
                'metadata' => ['sale_code' => $sale->kode_transaksi],
            ]);
        });
    }
}
