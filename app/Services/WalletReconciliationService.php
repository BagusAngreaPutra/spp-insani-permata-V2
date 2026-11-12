<?php

namespace App\Services;

use App\Models\WalletAccount;
use App\Models\WalletReconciliation;
use App\Models\WalletTopup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletReconciliationService
{
    public function run(string $provider, Carbon $date, bool $dryRun = false): array
    {
        $topups = WalletTopup::where('provider', $provider)
            ->where('status', 'paid')
            ->whereDate('paid_at', $date->toDateString())
            ->get();
        $matched = 0;
        $unmatched = 0;

        foreach ($topups as $topup) {
            $status = $topup->gateway_payment_id ? 'matched' : 'unmatched';
            $status === 'matched' ? $matched++ : $unmatched++;
            if ($dryRun) {
                continue;
            }
            WalletReconciliation::updateOrCreate(
                ['provider' => $provider, 'reconciliation_date' => $date->toDateString(), 'internal_reference' => $topup->reference],
                [
                    'external_reference' => $topup->gateway_payment_id,
                    'external_amount' => $topup->amount,
                    'internal_amount' => $topup->amount,
                    'status' => $status,
                    'details' => ['topup_id' => $topup->id],
                ],
            );
        }

        $negative = WalletAccount::where(function ($query) {
            $query->where('available_balance', '<', 0)->orWhere('held_balance', '<', 0);
        })->count();

        return compact('matched', 'unmatched', 'negative');
    }

    public function auditBalances(): array
    {
        $issues = [];
        WalletAccount::query()->chunkById(200, function ($accounts) use (&$issues): void {
            foreach ($accounts as $account) {
                $credit = (float) $account->ledgerEntries()->where('account_code', 'wallet_available')->where('direction', 'credit')->sum('amount');
                $debit = (float) $account->ledgerEntries()->where('account_code', 'wallet_available')->where('direction', 'debit')->sum('amount');
                if (abs(($credit - $debit) - (float) $account->available_balance) > 0.01) {
                    $issues[] = ['wallet_account_id' => $account->id, 'cached' => (float) $account->available_balance, 'ledger' => $credit - $debit];
                }
            }
        });
        return $issues;
    }
}
