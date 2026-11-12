<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    protected $table = 'wallet_ledger_entries';

    protected $fillable = [
        'wallet_journal_id', 'wallet_account_id', 'account_code', 'direction',
        'amount', 'balance_after', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'wallet_journal_id');
    }

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }
}
