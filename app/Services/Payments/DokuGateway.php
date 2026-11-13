<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Data\GatewayPayment;
use App\Models\Siswa;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DokuGateway implements PaymentGateway
{
    public function createTopup(WalletTopup $topup, Siswa $student): GatewayPayment
    {
        $clientId = (string) config('services.doku.client_id');
        $secret = (string) config('services.doku.secret_key');
        if ($clientId === '' || $secret === '') throw new RuntimeException('DOKU_CLIENT_ID dan DOKU_SECRET_KEY belum dikonfigurasi.');

        $path = '/checkout/v1/payment';
        $body = ['order' => ['amount' => (int) round((float) $topup->amount), 'invoice_number' => $topup->reference, 'currency' => 'IDR', 'callback_url_result' => route('siswa.dompet.index')], 'payment' => ['payment_due_date' => (int) config('services.doku.payment_due_date', 60)], 'customer' => ['id' => (string) $student->id, 'name' => $student->nama, 'email' => $student->email, 'phone' => $student->no_hp, 'country' => 'ID']];
        $requestId = (string) Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $headers = $this->headers($clientId, $requestId, $timestamp, $path, $body, $secret);
        $response = Http::withHeaders($headers)->acceptJson()->timeout(20)->post(rtrim((string) config('services.doku.base_url'), '/').$path, $body);
        if ($response->failed()) throw new RuntimeException('DOKU gagal membuat transaksi: '.$response->body());
        $data = $response->json();
        $payment = $data['response']['payment'] ?? [];
        return new GatewayPayment('doku', $topup->reference, $payment['token_id'] ?? $requestId, 'pending', $payment['url'] ?? null, raw: $data);
    }

    public function getStatus(string $reference): GatewayPayment
    {
        $clientId = (string) config('services.doku.client_id');
        $secret = (string) config('services.doku.secret_key');
        if ($clientId === '' || $secret === '') throw new RuntimeException('DOKU_CLIENT_ID dan DOKU_SECRET_KEY belum dikonfigurasi.');
        $path = '/orders/v1/status/'.urlencode($reference);
        $requestId = (string) Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $raw = "Client-Id:$clientId\nRequest-Id:$requestId\nRequest-Timestamp:$timestamp\nRequest-Target:$path";
        $response = Http::withHeaders(['Client-Id' => $clientId, 'Request-Id' => $requestId, 'Request-Timestamp' => $timestamp, 'Signature' => 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, $secret, true))])->acceptJson()->timeout(20)->get(rtrim((string) config('services.doku.base_url'), '/').$path);
        if ($response->failed()) throw new RuntimeException('DOKU gagal mengambil status transaksi: '.$response->body());
        $data = $response->json();
        $status = strtoupper((string) data_get($data, 'transaction.status', data_get($data, 'response.transaction.status', 'PENDING')));
        return new GatewayPayment('doku', (string) (data_get($data, 'order.invoice_number') ?: $reference), (string) (data_get($data, 'transaction.original_request_id') ?: ''), match ($status) { 'SUCCESS' => 'paid', 'FAILED' => 'failed', 'EXPIRED' => 'expired', default => 'pending' }, raw: $data);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $clientId = (string) config('services.doku.client_id');
        $secret = (string) config('services.doku.secret_key');
        $requestId = $this->header($headers, 'request-id');
        $timestamp = $this->header($headers, 'request-timestamp');
        $signature = $this->header($headers, 'signature');
        if ($clientId === '' || $secret === '' || $requestId === '' || $timestamp === '' || $signature === '') return false;
        $path = parse_url(request()->getUri(), PHP_URL_PATH) ?: '/webhooks/payment-gateway/doku';
        $expected = $this->signature($clientId, $requestId, $timestamp, $path, $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function headers(string $clientId, string $requestId, string $timestamp, string $path, array $body, string $secret): array
    { return ['Client-Id' => $clientId, 'Request-Id' => $requestId, 'Request-Timestamp' => $timestamp, 'Signature' => $this->signature($clientId, $requestId, $timestamp, $path, $body, $secret), 'Content-Type' => 'application/json']; }

    private function signature(string $clientId, string $requestId, string $timestamp, string $path, array $body, string $secret): string
    { $json = json_encode($body, JSON_UNESCAPED_SLASHES); $digest = base64_encode(hash('sha256', $json, true)); $raw = "Client-Id:$clientId\nRequest-Id:$requestId\nRequest-Timestamp:$timestamp\nRequest-Target:$path\nDigest:$digest"; return 'HMACSHA256='.base64_encode(hash_hmac('sha256', $raw, $secret, true)); }

    private function header(array $headers, string $name): string
    { foreach ($headers as $key => $value) if (strtolower($key) === $name) return is_array($value) ? (string) ($value[0] ?? '') : (string) $value; return ''; }
}
