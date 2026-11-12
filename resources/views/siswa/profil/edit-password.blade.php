@extends('layouts.app')
@include('layouts.sidebar-siswa')
@section('title', 'Ubah Password')
@section('content')
<div class="main-content">
    @include('layouts.header-siswa')
    <div class="content-area account-page">
        <div class="account-heading">
            <h1>Ubah Password</h1>
            <p>Perbarui kata sandi untuk masuk ke portal siswa.</p>
        </div>
        <section class="account-panel account-password-panel" aria-label="Formulir ubah password">
            <form action="{{ route('siswa.profil.updatePassword') }}" method="POST" class="account-form">
                @csrf
                @method('PUT')
                <input type="text" name="username" value="{{ Auth::guard('siswa')->user()->username }}" autocomplete="username" hidden>
                <div>
                    <label for="student_current_password">Password Lama</label>
                    <input id="student_current_password" type="password" name="current_password" autocomplete="current-password" required>
                    <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                </div>
                <div>
                    <label for="student_new_password">Password Baru</label>
                    <input id="student_new_password" type="password" name="new_password" autocomplete="new-password" required>
                    <x-input-error :messages="$errors->get('new_password')" class="mt-2" />
                </div>
                <div>
                    <label for="student_password_confirmation">Konfirmasi Password Baru</label>
                    <input id="student_password_confirmation" type="password" name="new_password_confirmation" autocomplete="new-password" required>
                    <x-input-error :messages="$errors->get('new_password_confirmation')" class="mt-2" />
                </div>
                <div class="account-actions">
                    <button type="submit" class="btn btn-success">Simpan perubahan</button>
                    <a href="{{ route('siswa.profil.index') }}" class="btn btn-outline-secondary">Kembali ke profil</a>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
