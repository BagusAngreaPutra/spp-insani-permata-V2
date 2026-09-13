@extends('layouts.app')
@include('layouts.sidebar')

@section('title', 'Pengaturan Diskon SPP')

@push('page-styles')
<style>
    .spp-discount-page { max-width: 980px; margin: 0 auto; }
    .spp-discount-layout { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 18px; align-items: start; }
    .spp-discount-card,
    .spp-discount-summary { overflow: hidden; background: #fff; border: 1px solid #e4e7ec; border-radius: 14px; box-shadow: 0 8px 24px rgba(16, 24, 40, .05); }
    .spp-discount-card-header { padding: 18px 20px; border-bottom: 1px solid #eaecf0; }
    .spp-discount-card-header h3 { display: flex; gap: 9px; align-items: center; margin: 0; color: #101828; font-size: 15px; font-weight: 750; }
    .spp-discount-card-header p { margin: 5px 0 0; color: #667085; font-size: 11px; }
    .spp-discount-form { padding: 20px; }
    .spp-discount-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .spp-field.is-wide { grid-column: 1 / -1; }
    .spp-field label { display: block; margin-bottom: 7px; color: #344054; font-size: 11px; font-weight: 700; }
    .spp-input-wrap { position: relative; }
    .spp-field input[type="text"],
    .spp-field input[type="date"] { width: 100%; height: 42px; padding: 9px 12px; color: #1d2939; background: #fff; border: 1px solid #d0d5dd; border-radius: 9px; outline: none; font-size: 12px; }
    .spp-field input:focus { border-color: #479b79; box-shadow: 0 0 0 3px rgba(37, 132, 93, .1); }
    .spp-field small { display: block; margin-top: 6px; color: #667085; font-size: 10px; line-height: 1.5; }
    .spp-switch-row { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 13px 14px; background: #f8fafb; border: 1px solid #e4e7ec; border-radius: 10px; }
    .spp-switch-copy strong { display: block; color: #344054; font-size: 12px; }
    .spp-switch-copy span { display: block; margin-top: 3px; color: #667085; font-size: 10px; }
    .spp-switch { position: relative; flex: 0 0 auto; width: 42px; height: 24px; }
    .spp-switch input { position: absolute; opacity: 0; pointer-events: none; }
    .spp-switch span { position: absolute; inset: 0; background: #98a2b3; border-radius: 999px; cursor: pointer; transition: .2s; }
    .spp-switch span::after { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(16, 24, 40, .25); content: ''; transition: .2s; }
    .spp-switch input:checked + span { background: #25845d; }
    .spp-switch input:checked + span::after { transform: translateX(18px); }
    .spp-error { margin-top: 6px; color: #b42318; font-size: 10px; }
    .spp-form-actions { display: flex; justify-content: flex-end; margin-top: 18px; padding-top: 16px; border-top: 1px solid #eaecf0; }
    .spp-save-button { display: inline-flex; gap: 8px; align-items: center; min-height: 40px; padding: 9px 16px; color: #fff; background: #25845d; border: 1px solid #25845d; border-radius: 9px; font-size: 11px; font-weight: 700; cursor: pointer; }
    .spp-discount-summary { padding: 18px; }
    .spp-discount-summary-icon { display: grid; place-items: center; width: 38px; height: 38px; margin-bottom: 14px; color: #175c43; background: #eaf7f1; border-radius: 10px; font-size: 16px; }
    .spp-discount-summary h3 { margin: 0 0 12px; color: #101828; font-size: 13px; }
    .spp-summary-row { padding: 10px 0; border-top: 1px solid #eaecf0; }
    .spp-summary-row span { display: block; color: #667085; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
    .spp-summary-row strong { display: block; margin-top: 4px; color: #1d2939; font-size: 12px; }
    .spp-help { margin: 13px 0 0; padding: 10px; color: #475467; background: #f8fafb; border-radius: 8px; font-size: 10px; line-height: 1.5; }
    @media (max-width: 760px) {
        .spp-discount-layout { grid-template-columns: 1fr; }
        .spp-discount-grid { grid-template-columns: 1fr; }
        .spp-field.is-wide { grid-column: auto; }
    }
</style>
@endpush

@section('content')
<div class="main-content">
    @include('layouts.header')

    <div class="content-area">
        <div class="spp-discount-page">
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="page-header">
                <div>
                    <p class="page-eyebrow">Pengaturan data</p>
                    <h2 class="page-title"><i class="fas fa-percent"></i> Diskon SPP</h2>
                    <p class="page-subtitle">Atur nominal dan periode diskon pelunasan tagihan SPP.</p>
                </div>
            </div>

            <div class="spp-discount-layout">
                <section class="spp-discount-card">
                    <header class="spp-discount-card-header">
                        <h3><i class="fas fa-sliders"></i> Ketentuan diskon</h3>
                        <p>Perubahan berlaku untuk transaksi baru dan perhitungan ulang saat koreksi.</p>
                    </header>

                    <form class="spp-discount-form" method="POST" action="{{ route('pengaturan.diskon-spp.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="spp-discount-grid">
                            <div class="spp-field is-wide">
                                <label for="nominal">Nominal diskon</label>
                                <div class="spp-input-wrap">
                                    <input id="nominal" type="text" name="nominal" value="{{ old('nominal', (int) ($setting?->nominal ?? $configuration['amount'])) }}" inputmode="numeric" data-rupiah required>
                                </div>
                                @error('nominal')<div class="spp-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="spp-field">
                                <label for="berlaku_mulai">Mulai berlaku</label>
                                <input id="berlaku_mulai" type="date" name="berlaku_mulai" value="{{ old('berlaku_mulai', $setting?->berlaku_mulai?->format('Y-m-d')) }}">
                                @error('berlaku_mulai')<div class="spp-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="spp-field">
                                <label for="berlaku_sampai">Berlaku sampai</label>
                                <input id="berlaku_sampai" type="date" name="berlaku_sampai" value="{{ old('berlaku_sampai', $setting?->berlaku_sampai?->format('Y-m-d')) }}">
                                @error('berlaku_sampai')<div class="spp-error">{{ $message }}</div>@enderror
                                <small>Kosongkan kedua tanggal untuk mengikuti jatuh tempo masing-masing tagihan SPP.</small>
                            </div>

                            <div class="spp-field is-wide">
                                <div class="spp-switch-row">
                                    <div class="spp-switch-copy">
                                        <strong>Aktifkan diskon SPP</strong>
                                        <span>Jika dimatikan, transaksi baru tidak menerima diskon.</span>
                                    </div>
                                    <label class="spp-switch" aria-label="Aktifkan diskon SPP">
                                        <input type="hidden" name="aktif" value="0">
                                        <input type="checkbox" name="aktif" value="1" {{ old('aktif', $setting?->aktif ?? true) ? 'checked' : '' }}>
                                        <span aria-hidden="true"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="spp-form-actions">
                            <button class="spp-save-button" type="submit"><i class="fas fa-floppy-disk"></i> Simpan pengaturan</button>
                        </div>
                    </form>
                </section>

                <aside class="spp-discount-summary">
                    <div class="spp-discount-summary-icon"><i class="fas fa-tag"></i></div>
                    <h3>Pengaturan saat ini</h3>
                    <div class="spp-summary-row"><span>Status</span><strong>{{ $configuration['active'] ? 'Aktif' : 'Nonaktif' }}</strong></div>
                    <div class="spp-summary-row"><span>Nominal</span><strong>Rp {{ number_format($configuration['amount'], 0, ',', '.') }}</strong></div>
                    <div class="spp-summary-row">
                        <span>Periode</span>
                        <strong>
                            @if($configuration['custom_period'])
                                {{ \Carbon\Carbon::parse($configuration['valid_from'])->translatedFormat('d M Y') }}–{{ \Carbon\Carbon::parse($configuration['valid_until'])->translatedFormat('d M Y') }}
                            @else
                                Mengikuti jatuh tempo SPP
                            @endif
                        </strong>
                    </div>
                    <p class="spp-help">Diskon hanya diberikan saat tagihan SPP dilunasi penuh dalam periode yang berlaku.</p>
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection
