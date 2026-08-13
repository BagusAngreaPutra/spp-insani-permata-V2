<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LogAktivitas;
use App\Models\Siswa; // tambahkan model Siswa
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('siswa.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Ambil siswa berdasarkan username
        $siswa = Siswa::where('username', $request->username)->first();

        $hashValid = $siswa && Hash::check($request->password, (string) $siswa->password);
        $legacyPasswordValid = $siswa
            && is_string($siswa->password_raw)
            && hash_equals($siswa->password_raw, (string) $request->password);

        if ($hashValid || $legacyPasswordValid) {
            // Akun hasil impor lama dapat memiliki hash berlapis. Perbaiki hash
            // secara transparan setelah kredensial lama berhasil diverifikasi.
            if (!$hashValid) {
                $siswa->password = $request->password;
                $siswa->save();
            }

            // Login manual
            Auth::guard('siswa')->login($siswa);
            $request->session()->regenerate();

            // Catat log aktivitas
            LogAktivitas::create([
                'aktor_type' => 'siswa',
                'aktor_id'   => $siswa->id,
                'aktivitas'  => 'Login ke sistem',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended(route('siswa.dashboard'));
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $idSiswa = Auth::guard('siswa')->id();

        Auth::guard('siswa')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        LogAktivitas::create([
            'aktor_type' => 'siswa',
            'aktor_id'   => $idSiswa,
            'aktivitas'  => 'Logout dari sistem',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('siswa.login');
    }
}
