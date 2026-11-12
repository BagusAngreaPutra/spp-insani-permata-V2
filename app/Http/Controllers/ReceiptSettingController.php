<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\ReceiptSetting;
use Illuminate\Http\Request;

class ReceiptSettingController extends Controller
{
    public function edit()
    {
        return view('pengaturan.kwitansi', ['setting' => ReceiptSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['numbering_period' => 'required|in:yearly,monthly', 'show_officer' => 'required|boolean']);
        ReceiptSetting::updateOrCreate(['id' => 1], $data);
        LogAktivitas::create(['aktor_type' => 'admin', 'aktor_id' => auth()->id(), 'aktivitas' => 'Mengubah pengaturan kwitansi: '.json_encode($data), 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        return back()->with('success', 'Pengaturan kwitansi disimpan. Nomor kwitansi lama tetap berlaku.');
    }
}
