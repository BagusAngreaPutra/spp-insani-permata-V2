<?php

namespace Tests\Unit;

use App\Models\Siswa;
use App\Models\WalletTopup;
use App\Services\Payments\FakePaymentGateway;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_fake_gateway_creates_a_pending_topup_payment(): void
    {
        $topup = new WalletTopup([
            'reference' => 'TOPUP-TEST-1',
            'amount' => '25000.00',
        ]);
        $topup->id = 99;

        $student = new Siswa(['nama' => 'Siswa Test']);
        $payment = (new FakePaymentGateway())->createTopup($topup, $student);

        $this->assertSame('fake', $payment->provider);
        $this->assertSame('TOPUP-TEST-1', $payment->reference);
        $this->assertSame('pending', $payment->status);
        $this->assertNotEmpty($payment->redirectUrl);
    }
}
