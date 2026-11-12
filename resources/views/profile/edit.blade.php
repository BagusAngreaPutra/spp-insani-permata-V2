@extends('layouts.app')
@include('layouts.sidebar')
@section('title', 'Profil Akun')
@section('content')
<div class="main-content">
    @include('layouts.header')
    <div class="content-area account-page">
        <div class="account-heading">
            <h1>Profil Akun</h1>
            <p>Kelola informasi akun dan kata sandi Anda.</p>
        </div>
        @if(session('status') === 'profile-updated')
            <div class="alert alert-success" role="status">Informasi akun berhasil diperbarui.</div>
        @elseif(session('status') === 'password-updated')
            <div class="alert alert-success" role="status">Kata sandi berhasil diperbarui.</div>
        @endif
        <div class="account-grid">
            <section class="account-panel" aria-labelledby="account-information-title">
                <header>
                    <h2 id="account-information-title">Informasi Akun</h2>
                    <p>Perbarui nama dan username yang digunakan untuk masuk.</p>
                </header>
                <form method="POST" action="{{ route('profile.update') }}" class="account-form">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="nama_admin">Nama Admin</label>
                        <input id="nama_admin" name="nama_admin" type="text" value="{{ old('nama_admin', $admin->nama_admin) }}" required autocomplete="name">
                        <x-input-error :messages="$errors->get('nama_admin')" class="mt-2" />
                    </div>
                    <div>
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username', $admin->username) }}" required autocomplete="username">
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>
                    <div class="account-actions"><button type="submit" class="btn btn-success">Simpan informasi</button></div>
                </form>
            </section>
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
