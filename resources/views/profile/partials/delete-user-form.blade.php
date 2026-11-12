<section class="account-panel account-panel-danger" aria-labelledby="account-delete-title">
    <header>
        <h2 id="account-delete-title">Hapus Akun</h2>
        <p>Penghapusan akun bersifat permanen. Pastikan data yang diperlukan sudah dicadangkan.</p>
    </header>
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">Hapus akun</button>
</section>
<div class="modal fade account-modal" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteAccountModalTitle">Hapus akun ini?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Masukkan kata sandi saat ini untuk mengonfirmasi penghapusan permanen akun Anda.</p>
                    <input type="text" name="username" value="{{ $admin->username }}" autocomplete="username" hidden>
                    <label for="account_delete_password">Kata Sandi Saat Ini</label>
                    <input id="account_delete_password" name="password" type="password" class="form-control" required autocomplete="current-password">
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                    <div class="account-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus akun permanen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@if($errors->userDeletion->isNotEmpty())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteAccountModal')).show();
    });
</script>
@endif
