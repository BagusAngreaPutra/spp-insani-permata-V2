@extends('layouts.app')
@include('layouts.sidebar-siswa')
@section('title', 'Profil Saya')
@section('content')
<div class="main-content">
    @include('layouts.header-siswa')
    <div class="content-area account-page">
        <div class="account-heading">
            <h1>Profil Saya</h1>
            <p>Informasi identitas dan akun siswa Anda.</p>
        </div>
        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        <section class="account-panel" aria-label="Identitas siswa">
            <dl class="account-details">
                <div><dt>Nama Lengkap</dt><dd>{{ $siswa->nama }}</dd></div>
                <div><dt>NIS</dt><dd>{{ $siswa->nis }}</dd></div>
                <div><dt>Kelas</dt><dd>{{ $siswa->kelas?->kelas ?? '-' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $siswa->email ?? '-' }}</dd></div>
            </dl>
            <a href="{{ route('siswa.profil.editPassword') }}" class="btn btn-warning">Ubah Password</a>
        </section>
    </div>
</div>
@endsection
