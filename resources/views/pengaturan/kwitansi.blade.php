@extends('layouts.app')
@include('layouts.sidebar')
@section('title', 'Pengaturan Kwitansi')
@section('content')
<div class="main-content receipt-settings-page">
    @include('layouts.header')
    <div class="content-area">
        <header class="receipt-settings-page-heading"><h1 class="page-title">Pengaturan kwitansi</h1><p>Atur urutan nomor dan nama petugas pada bukti pembayaran.</p></header>
        @if(session('success'))<div class="receipt-settings-notice" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="receipt-settings-notice is-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="receipt-settings-form" method="POST" action="{{ route('pengaturan.kwitansi.update') }}">
            @csrf @method('PUT')
            <section class="receipt-settings-section">
            <div class="receipt-settings-heading"><span class="receipt-settings-icon"><i class="fas fa-list-ol" aria-hidden="true"></i></span><div><h2>Urutan nomor kwitansi</h2><p>Pilih kapan urutan dimulai kembali dari nomor 1.</p></div></div>
            <div class="receipt-settings-field">
            <label for="numbering_period">Periode penomoran</label>
            <select name="numbering_period" id="numbering_period" aria-describedby="numberingHelp">
                <option value="yearly" @selected(old('numbering_period', $setting->numbering_period) === 'yearly')>Tahunan — mulai ulang setiap Januari</option>
                <option value="monthly" @selected(old('numbering_period', $setting->numbering_period) === 'monthly')>Bulanan — mulai ulang setiap awal bulan</option>
            </select>
            <p id="numberingHelp">Nomor lama tetap berlaku. Mode bulanan menyertakan bulan pada nomor agar setiap kwitansi tetap berbeda.</p>
            </div>
            </section>
            <section class="receipt-settings-section">
            <div class="receipt-settings-heading"><span class="receipt-settings-icon"><i class="fas fa-user-pen" aria-hidden="true"></i></span><div><h2>Nama petugas administrasi</h2><p>Gunakan nama akun yang mencatat pembayaran.</p></div></div>
            <div class="receipt-settings-field">
            <label for="show_officer">Nama petugas pada kwitansi</label>
            <select name="show_officer" id="show_officer" aria-describedby="officerHelp">
                <option value="0" @selected(!old('show_officer', $setting->show_officer))>Tanpa nama petugas</option>
                <option value="1" @selected(old('show_officer', $setting->show_officer))>Cantumkan nama akun yang menginput</option>
            </select>
            <p id="officerHelp">Kwitansi lama yang belum mencatat petugas tetap memakai label “Petugas administrasi”.</p>
            </div>
            </section>
            <div class="receipt-settings-footer transaction-actions"><button type="submit" class="transaction-action is-primary btn-primary"><i class="fas fa-check" aria-hidden="true"></i>Simpan pengaturan</button></div>
        </form>
    </div>
</div>
@endsection
