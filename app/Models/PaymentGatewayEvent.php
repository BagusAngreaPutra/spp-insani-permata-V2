<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayEvent extends Model
{
    protected $table = 'payment_gateway_events';

    protected $fillable = [
        'provider', 'event_id', 'event_type', 'signature_valid', 'payload',
        'processed_at', 'processing_error',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
