<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\LogAktivitas; // ✅ tambahkan ini

class AdminAuthController extends Controller
{
    /**
     * 🔹 Tampilkan form login untuk ADMIN
     */
    public function showLoginForm(): View
    {
        return view('auth.login'); // blade khusus admin
    }

    /**
     * 🔹 Proses login ADMIN
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate();

            // ✅ Catat log aktivitas LOGIN admin
            $admin = Auth::guard('web')->user();
            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id'   => $admin->id,
                'aktivitas'  => 'Admin login: ' . $admin->nama_admin . ' (ID: ' . $admin->id . ')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($admin->hasAnyPermission(['tagihan.manage', 'pembayaran.process'])) {
                return redirect()->route('tagihan.index.grouped');
            }

            if ($admin->hasPermission('riwayat.view')) {
                return redirect()->route('riwayat.index');
            }

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * 🔹 Logout ADMIN
     */
    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard('web')->user();
        if ($admin) {
            // ✅ Catat log aktivitas LOGOUT admin sebelum logout
            LogAktivitas::create([
                'aktor_type' => 'admin',
                'aktor_id'   => $admin->id,
                'aktivitas'  => 'Admin logout: ' . $admin->nama_admin . ' (ID: ' . $admin->id . ')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
