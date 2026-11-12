<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletJournal extends Model
{
    protected $table = 'wallet_journals';

    protected $fillable = [
        'reference', 'type', 'status', 'siswa_id', 'amount', 'idempotency_key',
        'description', 'metadata', 'posted_at', 'reversed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_journal_id');
    }

    public function topups(): HasMany
    {
        return $this->hasMany(WalletTopup::class, 'wallet_journal_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(WalletPayment::class, 'wallet_journal_id');
    }
}
