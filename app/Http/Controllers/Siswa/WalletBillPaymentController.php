<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\WalletBillPaymentService;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class WalletBillPaymentController extends Controller
{
    public function __construct(
        private readonly WalletLedgerService $wallets,
        private readonly WalletBillPaymentService $payments,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tagihan_ids' => ['required', 'array', 'min:1', 'max:20'],
            'tagihan_ids.*' => ['integer', 'distinct'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $student = $request->user('siswa');
            $account = $this->wallets->getOrCreateAccount($student);
            $idempotencyKey = $validated['idempotency_key']
                ?? $request->header('Idempotency-Key')
                ?? 'bill-request:'.Str::uuid();
            $this->payments->pay($account, $validated['tagihan_ids'], $idempotencyKey);

            return redirect()->route('siswa.tagihan.index')->with('success', 'Tagihan berhasil dibayar melalui dompet.');
        } catch (RuntimeException $exception) {
            return redirect()->route('siswa.tagihan.index')->withErrors(['tagihan_ids' => $exception->getMessage()]);
        }
    }
}
