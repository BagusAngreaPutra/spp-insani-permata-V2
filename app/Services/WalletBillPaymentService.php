<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\WalletAccount;
use App\Models\WalletPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletBillPaymentService
{
    public function __construct(private readonly WalletLedgerService $ledger) {}

    /**
     * Pay the current remaining balance of one or more bills belonging to the wallet owner.
     * The entire request is atomic: either all bills and the wallet are updated, or none are.
     */
    public function pay(WalletAccount|int $account, array $billIds, ?string $idempotencyKey = null): array
    {
        $billIds = collect($billIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        if ($billIds === []) {
            throw new RuntimeException('Pilih minimal satu tagihan.');
        }

        return DB::transaction(function () use ($account, $billIds, $idempotencyKey): array {
            $wallet = $account instanceof WalletAccount
                ? WalletAccount::whereKey($account->getKey())->lockForUpdate()->firstOrFail()
                : WalletAccount::whereKey($account)->lockForUpdate()->firstOrFail();
            if ($wallet->status !== 'active') {
                throw new RuntimeException('Wallet tidak aktif.');
            }

            $existingJournal = $idempotencyKey
                ? \App\Models\WalletJournal::where('idempotency_key', $idempotencyKey)->first()
                : null;
            if ($existingJournal) {
                return WalletPayment::where('wallet_journal_id', $existingJournal->getKey())->get()->all();
            }

            $bills = Tagihan::where('siswa_id', $wallet->siswa_id)
                ->whereIn('id', $billIds)
                ->lockForUpdate()
                ->with('pembayaran')
                ->get()
                ->keyBy('id');

            if ($bills->count() !== count($billIds)) {
                throw new RuntimeException('Salah satu tagihan tidak ditemukan atau bukan milik siswa ini.');
            }

            $amounts = [];
            foreach ($bills as $bill) {
                if (!$bill->is_active) {
                    throw new RuntimeException('Tagihan '.$bill->nama_tagihan.' tidak aktif.');
                }

                $paid = (float) $bill->pembayaran->sum('jumlah_bayar') + (float) $bill->pembayaran->sum('diskon');
                $remaining = max(0, (float) $bill->nominal - $paid);
                if ($remaining <= 0) {
                    throw new RuntimeException('Tagihan '.$bill->nama_tagihan.' sudah lunas.');
                }
                $amounts[$bill->id] = number_format($remaining, 2, '.', '');
            }

            $total = array_sum(array_map('floatval', $amounts));
            $journal = $this->ledger->debit(
                $wallet,
                number_format($total, 2, '.', ''),
                'bill_payment',
                'school_revenue',
                $idempotencyKey ?: 'bill-payment:'.Str::uuid(),
                ['bill_ids' => $billIds],
                'Pembayaran tagihan melalui dompet',
            );

            $payments = [];
            foreach ($bills as $bill) {
                $payment = Pembayaran::create([
                    'sekolah_id' => $bill->id_sekolah,
                    'tagihan_id' => $bill->id,
                    'siswa_id' => $wallet->siswa_id,
                    'jumlah_bayar' => $amounts[$bill->id],
                    'diskon' => 0,
                    'tanggal_bayar' => now()->toDateString(),
                    'tanggal_kwitansi' => now()->toDateString(),
                    'periode' => $bill->periode,
                    'periode_tahun' => $bill->tahun_ajaran_id,
                    'metode_bayar' => 'dompet',
                    'transaction_id' => $journal->reference,
                    'keterangan' => 'Pembayaran melalui dompet siswa',
                ]);

                $bill->forceFill(['status' => 'lunas'])->save();
                $payments[] = WalletPayment::create([
                    'wallet_account_id' => $wallet->getKey(),
                    'wallet_journal_id' => $journal->getKey(),
                    'source_type' => 'tagihan',
                    'source_id' => $bill->id,
                    'amount' => $amounts[$bill->id],
                    'status' => 'paid',
                    'idempotency_key' => 'bill-payment:'.$journal->reference.':'.$bill->id,
                    'paid_at' => now(),
                    'metadata' => ['pembayaran_id' => $payment->getKey()],
                ]);
            }

            return $payments;
        });
    }
}
