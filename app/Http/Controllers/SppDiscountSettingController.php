<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\SppDiscountSetting;
use App\Services\SppDiscountCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SppDiscountSettingController extends Controller
{
    public function edit(SppDiscountCalculator $calculator): View
    {
        return view('pengaturan.diskon-spp', [
            'setting' => SppDiscountSetting::query()->first(),
            'configuration' => $calculator->configuration(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'nominal' => preg_replace('/[^0-9]/', '', (string) $request->input('nominal')),
            'aktif' => $request->boolean('aktif'),
        ]);

        $validated = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'berlaku_mulai' => ['nullable', 'date', 'required_with:berlaku_sampai'],
            'berlaku_sampai' => ['nullable', 'date', 'required_with:berlaku_mulai', 'after_or_equal:berlaku_mulai'],
            'aktif' => ['required', 'boolean'],
        ], [
            'berlaku_mulai.required_with' => 'Tanggal mulai wajib diisi jika tanggal selesai ditentukan.',
            'berlaku_sampai.required_with' => 'Tanggal selesai wajib diisi jika tanggal mulai ditentukan.',
            'berlaku_sampai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $setting = SppDiscountSetting::query()->firstOrNew();
        $setting->fill($validated);
        $setting->updated_by = Auth::id();
        $setting->save();

        LogAktivitas::create([
            'aktor_type' => 'admin',
            'aktor_id' => Auth::id(),
            'aktivitas' => sprintf(
                'Memperbarui diskon SPP menjadi Rp %s (%s).',
                number_format((float) $setting->nominal, 0, ',', '.'),
                $setting->aktif ? 'aktif' : 'nonaktif'
            ),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('pengaturan.diskon-spp.edit')
            ->with('success', 'Pengaturan diskon SPP berhasil disimpan.');
    }
}
