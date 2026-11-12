<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopup extends Model
{
    protected $table = 'wallet_topups';

    protected $fillable = [
        'wallet_account_id', 'wallet_journal_id', 'provider', 'reference',
        'gateway_payment_id', 'amount', 'gateway_fee', 'status',
        'idempotency_key', 'expires_at', 'paid_at', 'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'wallet_journal_id');
    }
}
