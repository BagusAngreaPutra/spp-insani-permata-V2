<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke portal siswa sistem pembayaran SPP Permata Insani.">
    <title>Portal Siswa &middot; Permata Insani</title>
    @include('layouts.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('layouts.auth-styles')
</head>
<body>
    <main class="auth-shell">
        <section class="auth-showcase" aria-label="Informasi portal siswa">
            <a class="showcase-brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo Permata Insani Islamic School">
                <span><strong>Permata Insani</strong><span>Sistem Pembayaran SPP</span></span>
            </a>
            <div class="showcase-content">
                <div class="showcase-kicker">Portal informasi siswa</div>
                <h2>Informasi SPP lebih mudah <em>diakses.</em></h2>
                <p>Akses portal siswa untuk melihat informasi tagihan, riwayat pembayaran, dan data profil Anda.</p>
                <div class="showcase-points">
                    <div class="showcase-point"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 13h7M9 17h5"/></svg></i>Informasi tagihan siswa</div>
                    <div class="showcase-point"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg></i>Riwayat pembayaran tersimpan</div>
                </div>
            </div>
            <div class="showcase-footer"><span>SD IT Permata Insani Islamic School</span><span>NPSN 70029968</span></div>
        </section>

        <section class="auth-area">
            <div class="auth-panel">
                <a class="mobile-brand" href="{{ url('/') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Logo sekolah"><span><strong>Permata Insani</strong><span>Sistem Pembayaran SPP</span></span></a>
                <div class="form-badge"><i></i>Akses siswa</div>
                <h1>Selamat datang</h1>
                <p class="auth-subtitle">Masukkan akun siswa Anda untuk melanjutkan ke portal.</p>

                @if ($errors->any())
                    <div class="auth-error" role="alert"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 17h.01"/></svg><span>{{ $errors->first() }}</span></div>
                @endif

                <form method="POST" action="{{ route('siswa.login.store') }}">
                    @csrf
                    <div class="field">
                        <label for="username">Username</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                            <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" required autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <input id="password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-controls="password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                    </div>
                    <div class="form-options">
                        <div class="remember"><input id="remember_me" type="checkbox" name="remember"><label for="remember_me">Ingat saya</label></div>
                        <span class="secure-copy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>Akses khusus pengguna terdaftar</span>
                    </div>
                    <button class="submit" type="submit">Masuk ke portal siswa <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                </form>
                <a class="back" href="{{ url('/') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Kembali ke halaman utama</a>
                <p class="form-note">Gunakan akun yang telah diberikan oleh pihak sekolah.</p>
            </div>
        </section>
    </main>
    <script>
        document.querySelector('.password-toggle').addEventListener('click', function () {
            const input = document.getElementById(this.getAttribute('aria-controls'));
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            this.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    </script>
</body>
</html>
