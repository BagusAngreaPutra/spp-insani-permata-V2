<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPayment extends Model
{
    protected $table = 'wallet_payments';

    protected $fillable = [
        'wallet_account_id', 'wallet_journal_id', 'source_type', 'source_id',
        'amount', 'status', 'idempotency_key', 'paid_at', 'reversed_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'wallet_journal_id');
    }

    public function source()
    {
        return $this->morphTo();
    }
}
