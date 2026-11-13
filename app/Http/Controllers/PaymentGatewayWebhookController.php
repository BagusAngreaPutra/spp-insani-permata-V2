<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayEvent;
use App\Models\WalletTopup;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\WalletLedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentGatewayWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly WalletLedgerService $wallets,
    ) {}

    public function __invoke(Request $request, string $provider): Response
    {
        $payload = $request->all();
        $gateway = $this->gateways->driver($provider);

        if (!$gateway->verifyWebhook($payload, $request->headers->all())) {
            return response('Invalid webhook signature.', 403);
        }

        $eventId = $this->eventId($provider, $payload);
        $event = PaymentGatewayEvent::firstOrCreate(
            ['provider' => $provider, 'event_id' => $eventId],
            [
                'event_type' => $this->eventType($provider, $payload),
                'signature_valid' => true,
                'payload' => $payload,
            ],
        );

        if ($event->processed_at) {
            return response('Already processed.', 200);
        }

        try {
            DB::transaction(function () use ($event, $provider, $payload): void {
                $lockedEvent = PaymentGatewayEvent::whereKey($event->getKey())->lockForUpdate()->firstOrFail();
                if ($lockedEvent->processed_at) {
                    return;
                }

                $reference = $this->reference($provider, $payload);
                $topup = WalletTopup::where('reference', $reference)->lockForUpdate()->first();
                if (!$topup) {
                    throw new RuntimeException('Top up tidak ditemukan untuk webhook '.$reference.'.');
                }

                $status = $this->status($provider, $payload);
                if ($status === 'paid' && $topup->status !== 'paid') {
                    if (!$this->amountMatches($topup->amount, $this->amount($provider, $payload))) {
                        throw new RuntimeException('Nominal webhook tidak sama dengan nominal top up.');
                    }

                    $account = $topup->walletAccount()->lockForUpdate()->firstOrFail();
                    $journal = $this->wallets->credit(
                        $account,
                        $topup->amount,
                        'topup',
                        'gateway_clearing',
                        'topup:'.$topup->getKey(),
                        ['provider' => $provider, 'gateway_payload' => $payload],
                        'Top up melalui '.$provider,
                    );
                    $topup->forceFill([
                        'status' => 'paid',
                        'wallet_journal_id' => $journal->getKey(),
                        'gateway_payment_id' => $this->paymentId($provider, $payload) ?: $topup->gateway_payment_id,
                        'gateway_payload' => $payload,
                        'paid_at' => now(),
                    ])->save();
                } elseif (in_array($status, ['failed', 'expired'], true) && $topup->status === 'pending') {
                    $topup->forceFill(['status' => $status, 'gateway_payload' => $payload])->save();
                }

                $lockedEvent->forceFill(['processed_at' => now()])->save();
            });
        } catch (\Throwable $exception) {
            report($exception);
            $event->forceFill(['processing_error' => $exception->getMessage()])->save();
            return response('Webhook processing failed.', 500);
        }

        return response('OK', 200);
    }

    private function eventId(string $provider, array $payload): string
    {
        if ($provider === 'doku') {
            return hash('sha256', implode('|', ['doku', data_get($payload, 'order.invoice_number', ''), data_get($payload, 'transaction.status', ''), data_get($payload, 'transaction.original_request_id', '')]));
        }
        if (!empty($payload['event_id'] ?? null)) {
            return (string) $payload['event_id'];
        }
        if (!empty($payload['id'] ?? null)) {
            return (string) $payload['id'];
        }

        return hash('sha256', implode('|', [
            $provider,
            $payload['order_id'] ?? $payload['reference_id'] ?? '',
            $payload['transaction_status'] ?? $payload['status'] ?? $payload['event'] ?? '',
            $payload['transaction_id'] ?? $payload['payment_id'] ?? '',
            $payload['gross_amount'] ?? $payload['amount'] ?? '',
        ]));
    }

    private function eventType(string $provider, array $payload): string
    {
        if ($provider === 'doku') return 'doku.'.strtolower((string) data_get($payload, 'transaction.status', 'payment'));
        return (string) ($payload['event'] ?? $payload['transaction_status'] ?? $provider.'.payment');
    }

    private function reference(string $provider, array $payload): string
    {
        if ($provider === 'doku') return (string) data_get($payload, 'order.invoice_number', '');
        return (string) ($payload['order_id'] ?? $payload['reference_id'] ?? $payload['external_id'] ?? '');
    }

    private function status(string $provider, array $payload): string
    {
        if ($provider === 'doku') {
            return match (strtoupper((string) data_get($payload, 'transaction.status', 'PENDING'))) { 'SUCCESS' => 'paid', 'FAILED' => 'failed', 'EXPIRED' => 'expired', default => 'pending' };
        }
        $raw = strtolower((string) ($payload['transaction_status'] ?? $payload['status'] ?? $payload['event'] ?? 'pending'));
        return match (true) {
            in_array($raw, ['settlement', 'capture', 'succeeded', 'success', 'payment.capture'], true) => 'paid',
            in_array($raw, ['expire', 'expired', 'payment.expiry'], true) => 'expired',
            in_array($raw, ['deny', 'cancel', 'cancelled', 'failed', 'failure', 'payment.failure'], true) => 'failed',
            default => 'pending',
        };
    }

    private function amount(string $provider, array $payload): mixed
    {
        if ($provider === 'doku') return data_get($payload, 'order.amount');
        return $payload['gross_amount'] ?? $payload['amount'] ?? $payload['request_amount'] ?? null;
    }

    private function paymentId(string $provider, array $payload): ?string
    {
        if ($provider === 'doku') return (string) (data_get($payload, 'transaction.original_request_id') ?: data_get($payload, 'card_payment.payment_id') ?: '');
        $id = $payload['transaction_id'] ?? $payload['payment_id'] ?? $payload['id'] ?? null;
        return $id === null ? null : (string) $id;
    }

    private function amountMatches(mixed $expected, mixed $actual): bool
    {
        return $actual !== null && abs((float) $expected - (float) $actual) < 0.001;
    }
}
