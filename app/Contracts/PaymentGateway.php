<?php

namespace App\Contracts;

use App\Data\GatewayPayment;
use App\Models\Siswa;
use App\Models\WalletTopup;

interface PaymentGateway
{
    public function createTopup(WalletTopup $topup, Siswa $student): GatewayPayment;

    public function getStatus(string $reference): GatewayPayment;

    public function verifyWebhook(array $payload, array $headers = []): bool;
}
