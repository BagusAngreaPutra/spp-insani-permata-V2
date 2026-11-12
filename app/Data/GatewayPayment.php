<?php

namespace App\Data;

final class GatewayPayment
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly ?string $paymentId,
        public readonly string $status,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $token = null,
        public readonly array $raw = [],
    ) {}

    public function isSuccessful(): bool
    {
        return in_array(strtolower($this->status), ['paid', 'settlement', 'capture', 'succeeded', 'success'], true);
    }
}
