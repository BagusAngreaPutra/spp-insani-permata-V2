<?php

namespace App\Services;

use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BillDisplayName
{
    public function receipt(?Tagihan $bill, ?string $period = null): string
    {
        if (!$bill) { return 'Tagihan dihapus'; }
        $name = trim((string) ($bill->jenisPembayaran?->nama_pembayaran ?: $bill->nama_tagihan));
        $period ??= $bill->periode;
        if (preg_match('/\bspp\b/i', $name)) {
            return 'SPP'.$this->month($period, false);
        }
        if (Str::contains(Str::lower($name), 'activity school')) {
            return 'Activity School'.$this->academicYear($bill);
        }
        // Monthly extracurricular receipts must identify the bill period, not payment date.
        if ($bill->tipe === 'bulanan' || preg_match('/\b(karate|tpq)\b/i', $name)) {
            $name = preg_replace('/\s*-?\s*(?:Angkatan|Tahun Ajaran)\b.*$/iu', '', $name);
            return trim($name).$this->month($period, true);
        }
        return convertBulanToIndonesia($bill->nama_tagihan);
    }

    public function report(?Tagihan $bill, ?string $period = null): string
    {
        if (!$bill) { return 'Tagihan dihapus'; }
        $name = $this->receipt($bill, $period);
        if (preg_match('/\bspp\b/i', (string) ($bill->jenisPembayaran?->nama_pembayaran ?: $bill->nama_tagihan))) {
            return $name.$this->academicYear($bill);
        }
        return preg_replace('/\s*-?\s*Angkatan\s+\d{4}(?:\s*[\/-]\s*\d{4})?/iu', '', $name);
    }

    private function month(?string $period, bool $year): string
    {
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', (string) $period, $parts)) {
            return ' '.Str::upper(Carbon::create((int) $parts[1], (int) $parts[2], 1)->locale('id')->translatedFormat($year ? 'F Y' : 'F'));
        }
        return filled($period) ? ' - Periode '.$period : '';
    }

    private function academicYear(Tagihan $bill): string
    {
        $year = $bill->tahunAjaran?->label ?? $bill->jenisPembayaran?->tahunAjaran?->label;
        if (!$year && preg_match('/Tahun Ajaran\s+(\d{4}[\/-]\d{4})/i', (string) $bill->nama_tagihan, $parts)) { $year = $parts[1]; }
        return $year ? ' - Tahun Ajaran '.$year : '';
    }
}
