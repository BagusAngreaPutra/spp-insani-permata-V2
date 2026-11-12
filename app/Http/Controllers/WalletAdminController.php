<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayEvent;
use App\Models\WalletAccount;
use App\Models\WalletPayment;
use App\Models\WalletTopup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $accounts = WalletAccount::with('siswa.sekolah')
            ->when($search, fn ($query) => $query->whereHas('siswa', fn ($student) => $student->where('nama', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")))
            ->latest('id')->paginate(20)->withQueryString();
        $stats = [
            'accounts' => WalletAccount::count(),
            'balance' => WalletAccount::sum('available_balance'),
            'held' => WalletAccount::sum('held_balance'),
            'pending_topups' => WalletTopup::where('status', 'pending')->count(),
            'payments' => WalletPayment::where('status', 'paid')->count(),
            'webhook_errors' => PaymentGatewayEvent::whereNotNull('processing_error')->count(),
        ];
        return view('wallet.admin.index', compact('accounts', 'stats', 'search'));
    }
}
