<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * ✅ Tampilkan form login untuk ADMIN
     */
    public function create(): View
    {
        Log::info('[Login-Admin] Menampilkan halaman login admin');
        return view('auth.login'); // view admin login
    }

    /**
     * ✅ Proses login untuk ADMIN
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        Log::info('[Login-Admin] Input tervalidasi', [
            'username' => $credentials['username'],
        ]);

        if (Auth::guard('web')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            Log::info('[Login-Admin] Berhasil login sebagai ADMIN', [
                'username' => $credentials['username'],
            ]);
            $request->session()->regenerate();

            // 🔥 langsung arahkan ke dashboard admin
            return redirect()->route('dashboard');
        }

        Log::warning('[Login-Admin] Gagal login', [
            'username' => $credentials['username'],
        ]);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * ✅ Tampilkan form login untuk SISWA
     */
    public function createSiswa(): View
    {
        Log::info('[Login-Siswa] Menampilkan halaman login siswa');
        return view('siswa.auth.login'); // view siswa login
    }

    /**
     * ✅ Proses login untuk SISWA
     */
    public function storeSiswa(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        Log::info('[Login-Siswa] Input tervalidasi', [
            'username' => $credentials['username'],
        ]);

        if (Auth::guard('siswa')->attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            Log::info('[Login-Siswa] Berhasil login sebagai SISWA', [
                'username' => $credentials['username'],
            ]);
            $request->session()->regenerate();

            // 🔥 langsung arahkan ke dashboard siswa
            return redirect()->route('siswa.dashboard');
        }

        Log::warning('[Login-Siswa] Gagal login', [
            'username' => $credentials['username'],
        ]);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * ✅ Logout (ADMIN & SISWA)
     */
    public function destroy(Request $request): RedirectResponse
    {
        // default redirect
        $redirect = route('login');

        if (Auth::guard('web')->check()) {
            Log::info('[Logout] Logout ADMIN', [
                'id' => Auth::guard('web')->id(),
                'guard' => 'web',
            ]);
            Auth::guard('web')->logout();
            $redirect = route('login');
        } elseif (Auth::guard('siswa')->check()) {
            Log::info('[Logout] Logout SISWA', [
                'id' => Auth::guard('siswa')->id(),
                'guard' => 'siswa',
            ]);
            Auth::guard('siswa')->logout();
            $redirect = route('siswa.login');
        } else {
            Log::warning('[Logout] Tidak ada guard aktif');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[Logout] Sesi dihapus');

        return redirect($redirect);
    }

    /**
     * ✅ Logout khusus untuk siswa
     */
    public function destroySiswa(Request $request): RedirectResponse
    {
        Log::info('[Logout-Siswa] Logout dipanggil');
        Auth::guard('siswa')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('siswa.login');
    }
}
