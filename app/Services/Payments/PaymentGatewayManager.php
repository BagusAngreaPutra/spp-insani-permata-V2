<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $name = null): PaymentGateway
    {
        $driver = strtolower((string) ($name ?: config('services.payment_gateway.driver', 'fake')));

        if ($driver === 'fake' && app()->environment('production')) {
            throw new InvalidArgumentException('Fake payment gateway tidak boleh digunakan di production.');
        }

        return match ($driver) {
            'midtrans' => app(MidtransGateway::class),
            'fake', 'testing' => app(FakePaymentGateway::class),
            default => throw new InvalidArgumentException('Payment gateway tidak didukung: '.$driver),
        };
    }
}
