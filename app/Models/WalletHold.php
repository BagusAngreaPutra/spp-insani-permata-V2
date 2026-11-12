<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletHold extends Model
{
    protected $table = 'wallet_holds';

    protected $fillable = [
        'wallet_account_id', 'wallet_journal_id', 'capture_journal_id', 'release_journal_id', 'hold_reference', 'amount',
        'status', 'expires_at', 'captured_at', 'released_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'captured_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class, 'wallet_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'wallet_journal_id');
    }

    public function captureJournal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'capture_journal_id');
    }

    public function releaseJournal(): BelongsTo
    {
        return $this->belongsTo(WalletJournal::class, 'release_journal_id');
    }
}
