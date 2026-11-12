<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Siswa;
use App\Models\WalletTopup;

class FakePaymentGateway implements PaymentGateway
{
    public function createTopup(WalletTopup $topup, Siswa $student): GatewayPayment
    {
        return new GatewayPayment(
            provider: 'fake',
            reference: $topup->reference,
            paymentId: 'fake-'.$topup->id,
            status: 'pending',
            redirectUrl: route('siswa.dompet.topup.show', $topup),
            raw: ['fake' => true],
        );
    }

    public function getStatus(string $reference): GatewayPayment
    {
        return new GatewayPayment('fake', $reference, 'fake-'.$reference, 'pending', raw: ['fake' => true]);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return app()->environment(['local', 'testing']) && (app()->environment('testing') || (bool) ($payload['fake_signature_valid'] ?? false));
    }
}
