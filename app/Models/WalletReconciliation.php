<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletReconciliation extends Model
{
    protected $table = 'wallet_reconciliations';

    protected $fillable = [
        'provider', 'reconciliation_date', 'external_reference', 'internal_reference',
        'external_amount', 'internal_amount', 'status', 'details',
        'resolved_by_admin_id', 'resolved_at',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'external_amount' => 'decimal:2',
        'internal_amount' => 'decimal:2',
        'details' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by_admin_id');
    }
}
