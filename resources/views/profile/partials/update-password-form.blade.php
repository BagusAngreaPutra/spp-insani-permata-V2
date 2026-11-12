<section class="account-panel" aria-labelledby="account-password-title">
    <header>
        <h2 id="account-password-title">Perbarui Kata Sandi</h2>
        <p>Gunakan kata sandi yang panjang dan berbeda dari akun lain.</p>
    </header>
    <form method="POST" action="{{ route('password.update') }}" class="account-form">
        @csrf
        @method('PUT')
        <input type="text" name="username" value="{{ $admin->username }}" autocomplete="username" hidden>
        <div>
            <label for="account_current_password">Kata Sandi Saat Ini</label>
            <input id="account_current_password" name="current_password" type="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>
        <div>
            <label for="account_new_password">Kata Sandi Baru</label>
            <input id="account_new_password" name="password" type="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>
        <div>
            <label for="account_password_confirmation">Konfirmasi Kata Sandi Baru</label>
            <input id="account_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>
        <div class="account-actions"><button type="submit" class="btn btn-success">Simpan kata sandi</button></div>
    </form>
</section>
