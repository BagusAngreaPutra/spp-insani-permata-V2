<?php

namespace App\Http\Controllers\Siswa;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\WalletTopup;
use App\Services\WalletLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class WalletTopupController extends Controller
{
    public function __construct(
        private readonly WalletLedgerService $wallets,
        private readonly PaymentGateway $gateway,
    ) {}

    public function index(Request $request): View
    {
        $student = $request->user('siswa');
        $account = $this->wallets->getOrCreateAccount($student);
        $topups = WalletTopup::where('wallet_account_id', $account->getKey())
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('siswa.dompet.index', compact('account', 'topups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10000', 'max:5000000'],
        ], [
            'amount.min' => 'Minimal isi saldo adalah Rp10.000.',
            'amount.max' => 'Maksimal isi saldo adalah Rp5.000.000 per transaksi.',
        ]);

        $student = $request->user('siswa');
        $account = $this->wallets->getOrCreateAccount($student);
        $reference = 'TOPUP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
        $topup = WalletTopup::create([
            'wallet_account_id' => $account->getKey(),
            'provider' => (string) config('services.payment_gateway.driver', 'fake'),
            'reference' => $reference,
            'amount' => number_format((float) $validated['amount'], 2, '.', ''),
            'status' => 'pending',
            'idempotency_key' => 'topup-request:'.Str::uuid(),
            'expires_at' => now()->addHours(2),
        ]);

        try {
            $payment = $this->gateway->createTopup($topup, $student);
        } catch (\Throwable $exception) {
            report($exception);
            $topup->forceFill(['status' => 'failed'])->save();
            return redirect()->route('siswa.dompet.index')
                ->withInput()
                ->withErrors(['amount' => 'Top up gagal dibuat. Silakan coba lagi.']);
        }

        $topup->forceFill([
            'gateway_payment_id' => $payment->paymentId,
            'gateway_payload' => $payment->raw,
        ])->save();

        if ($payment->redirectUrl) {
            return redirect()->away($payment->redirectUrl);
        }

        return redirect()->route('siswa.dompet.topup.show', $topup)
            ->with('success', 'Permintaan top up berhasil dibuat.');
    }

    public function show(Request $request, WalletTopup $topup): View
    {
        $student = $request->user('siswa');
        $account = $this->wallets->getOrCreateAccount($student);
        abort_unless((int) $topup->wallet_account_id === (int) $account->getKey(), 404);

        return view('siswa.dompet.topup', compact('account', 'topup'));
    }
}
