<?php

namespace App\Console\Commands;

use App\Services\WalletReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WalletReconcile extends Command
{
    protected $signature = 'wallet:reconcile {--provider=midtrans} {--date=} {--dry-run} {--audit-balances}';
    protected $description = 'Rekonsiliasi top up wallet dan audit saldo ledger';

    public function handle(WalletReconciliationService $service): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today();
        $result = $service->run((string) $this->option('provider'), $date, (bool) $this->option('dry-run'));
        $this->info("Matched: {$result['matched']}; unmatched: {$result['unmatched']}; negative: {$result['negative']}");
        if ($this->option('audit-balances')) {
            $issues = $service->auditBalances();
            $this->info('Ledger balance issues: '.count($issues));
            if ($issues) {
                $this->table(['Wallet', 'Cached', 'Ledger'], array_map(fn ($row) => [$row['wallet_account_id'], $row['cached'], $row['ledger']], $issues));
                return self::FAILURE;
            }
        }
        return $result['negative'] > 0 || $result['unmatched'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
