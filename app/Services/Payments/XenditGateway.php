<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Siswa;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class XenditGateway implements PaymentGateway
{
    private string $baseUrl = 'https://api.xendit.co';
    private string $apiVersion = '2024-11-11';

    public function createTopup(WalletTopup $topup, Siswa $student): GatewayPayment
    {
        $key = (string) config('services.xendit.secret_key');
        if ($key === '') {
            throw new RuntimeException('XENDIT_SECRET_KEY belum dikonfigurasi.');
        }

        $response = Http::withBasicAuth($key, '')
            ->withHeaders(['api-version' => $this->apiVersion])
            ->acceptJson()->timeout(20)
            ->post($this->baseUrl.'/v3/payment_requests', [
                'reference_id' => $topup->reference,
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => (float) $topup->amount,
                'capture_method' => 'AUTOMATIC',
                'description' => 'Top up dompet siswa '.$student->nama,
                'metadata' => ['topup_reference' => $topup->reference, 'student_id' => (string) $student->id],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Xendit gagal membuat payment request: '.$response->body());
        }

        $data = $response->json();
        $action = collect($data['actions'] ?? [])->first(fn ($item) => !empty($item['url']) || !empty($item['value']));
        return new GatewayPayment(
            provider: 'xendit',
            reference: $topup->reference,
            paymentId: $data['payment_request_id'] ?? null,
            status: $this->mapStatus($data['status'] ?? 'REQUIRES_ACTION'),
            redirectUrl: $action['url'] ?? $action['value'] ?? null,
            raw: $data,
        );
    }

    public function getStatus(string $reference): GatewayPayment
    {
        $key = (string) config('services.xendit.secret_key');
        if ($key === '') {
            throw new RuntimeException('XENDIT_SECRET_KEY belum dikonfigurasi.');
        }
        $requestId = $reference;
        $response = Http::withBasicAuth($key, '')
            ->withHeaders(['api-version' => $this->apiVersion])
            ->acceptJson()->timeout(20)
            ->get($this->baseUrl.'/v3/payment_requests/'.urlencode($requestId));
        if ($response->failed()) {
            throw new RuntimeException('Xendit gagal mengambil status payment request: '.$response->body());
        }
        $data = $response->json();
        return new GatewayPayment('xendit', (string) ($data['reference_id'] ?? $reference), $data['payment_request_id'] ?? null, $this->mapStatus($data['status'] ?? 'PENDING'), raw: $data);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $expected = (string) config('services.xendit.webhook_token');
        $received = $headers['x-callback-token'] ?? $headers['X-Callback-Token'] ?? null;
        if (is_array($received)) {
            $received = $received[0] ?? null;
        }
        return $expected !== '' && is_string($received) && hash_equals($expected, $received);
    }

    private function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'SUCCEEDED', 'CAPTURE', 'SETTLED' => 'paid',
            'FAILED', 'CANCELED', 'CANCELLED' => 'failed',
            'EXPIRED' => 'expired',
            default => 'pending',
        };
    }
}
