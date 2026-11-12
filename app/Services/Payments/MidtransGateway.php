<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Siswa;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway implements PaymentGateway
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.midtrans.base_url', 'https://app.sandbox.midtrans.com'), '/');
    }

    public function createTopup(WalletTopup $topup, Siswa $student): GatewayPayment
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl.'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $topup->reference,
                    'gross_amount' => (int) round((float) $topup->amount),
                ],
                'customer_details' => [
                    'first_name' => $student->nama,
                    'email' => $student->email,
                    'phone' => $student->no_hp,
                ],
                'callbacks' => [
                    'finish' => route('siswa.dompet.index'),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Midtrans gagal membuat transaksi: '.$response->body());
        }

        $data = $response->json();
        return new GatewayPayment(
            provider: 'midtrans',
            reference: $topup->reference,
            paymentId: $data['token'] ?? null,
            status: 'pending',
            redirectUrl: $data['redirect_url'] ?? null,
            token: $data['token'] ?? null,
            raw: $data,
        );
    }

    public function getStatus(string $reference): GatewayPayment
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->get($this->baseUrl.'/v2/'.urlencode($reference).'/status');

        if ($response->failed()) {
            throw new RuntimeException('Midtrans gagal mengambil status transaksi: '.$response->body());
        }

        $data = $response->json();
        return new GatewayPayment(
            provider: 'midtrans',
            reference: $reference,
            paymentId: $data['transaction_id'] ?? null,
            status: $this->mapStatus($data),
            raw: $data,
        );
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');
        $expected = hash('sha512', (string) ($payload['order_id'] ?? '').(string) ($payload['status_code'] ?? '').(string) ($payload['gross_amount'] ?? '').$serverKey);
        return $signature !== '' && $serverKey !== '' && hash_equals($expected, $signature);
    }

    private function mapStatus(array $data): string
    {
        $status = strtolower((string) ($data['transaction_status'] ?? 'pending'));
        if ($status === 'settlement' || ($status === 'capture' && ($data['fraud_status'] ?? 'accept') === 'accept')) {
            return 'paid';
        }
        return match ($status) {
            'expire' => 'expired',
            'cancel', 'deny', 'failure' => 'failed',
            default => 'pending',
        };
    }
}
