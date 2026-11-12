<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WalletAccount extends Model
{
    protected $table = 'wallet_accounts';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'currency',
        'status',
        'available_balance',
        'held_balance',
        'daily_topup_limit',
        'daily_payment_limit',
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'held_balance' => 'decimal:2',
        'daily_topup_limit' => 'decimal:2',
        'daily_payment_limit' => 'decimal:2',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(WalletJournal::class, 'siswa_id', 'siswa_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class, 'wallet_account_id');
    }

    public function topups(): HasMany
    {
        return $this->hasMany(WalletTopup::class, 'wallet_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(WalletPayment::class, 'wallet_account_id');
    }

    public function holds(): HasMany
    {
        return $this->hasMany(WalletHold::class, 'wallet_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
