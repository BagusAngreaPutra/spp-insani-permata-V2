<?php

namespace App\Services;

use App\Models\JenisPembayaran;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

/** Database aggregates keep the existing period, discount and due-date rules. */
final class BillingWorkspace
{
    public function query($yearId = null)
    {
        $payments = Pembayaran::query()->select('tagihan_id')
            ->selectRaw('SUM(jumlah_bayar) AS paid, SUM(diskon) AS discount')->groupBy('tagihan_id');
        $bills = Tagihan::query()->where('tagihan.is_active', true)
            ->when($yearId, fn ($q) => $q->where('tagihan.tahun_ajaran_id', $yearId));
        $this->configuredPeriods($bills);
        $remaining = 'CASE WHEN tagihan.nominal - COALESCE(pay.paid, 0) - COALESCE(pay.discount, 0) > 0 THEN tagihan.nominal - COALESCE(pay.paid, 0) - COALESCE(pay.discount, 0) ELSE 0 END';
        $totals = $bills->leftJoinSub($payments, 'pay', 'pay.tagihan_id', '=', 'tagihan.id')
            ->select('tagihan.siswa_id')->selectRaw('COUNT(*) AS bill_count, SUM(tagihan.nominal) AS nominal, SUM(COALESCE(pay.paid, 0)) AS paid, SUM(COALESCE(pay.discount, 0)) AS discount')
            ->selectRaw("SUM({$remaining}) AS outstanding")
            ->selectRaw("SUM(CASE WHEN tagihan.tanggal_jatuh_tempo > ? THEN 0 ELSE {$remaining} END) AS due", [\Carbon\Carbon::today('Asia/Jakarta')->toDateString()])
            ->groupBy('tagihan.siswa_id');
        return Siswa::query()->with(['sekolah', 'kelas'])
            ->leftJoinSub($totals, 'bills', 'bills.siswa_id', '=', 'siswa.id')
            ->select('siswa.*')
            ->selectRaw('COALESCE(bills.bill_count, 0) AS total_tagihan, COALESCE(bills.nominal, 0) AS total_nominal, COALESCE(bills.paid, 0) AS total_dibayar, COALESCE(bills.discount, 0) AS total_diskon, COALESCE(bills.outstanding, 0) AS sisa_aktual, COALESCE(bills.due, 0) AS sisa_bayar')
            ->selectRaw($this->statusSql().' AS workspace_status');
    }

    public function statusSql(): string
    {
        return "CASE WHEN COALESCE(bills.bill_count, 0) = 0 THEN 'kosong' WHEN COALESCE(bills.outstanding, 0) <= 0 THEN 'lunas' WHEN COALESCE(bills.due, 0) <= 0 THEN 'belum_jatuh_tempo' WHEN COALESCE(bills.paid, 0) + COALESCE(bills.discount, 0) > 0 THEN 'sebagian' ELSE 'belum' END";
    }

    public function summary($query): array
    {
        $row = (clone $query)->reorder()->toBase()->select([
            DB::raw('COUNT(*) AS total_siswa'), DB::raw('COALESCE(SUM(bills.bill_count), 0) AS total_tagihan'),
            DB::raw('COALESCE(SUM(bills.nominal), 0) AS total_nominal'), DB::raw('COALESCE(SUM(bills.paid), 0) AS total_dibayar'),
            DB::raw('COALESCE(SUM(bills.discount), 0) AS total_diskon'), DB::raw('COALESCE(SUM(bills.outstanding), 0) AS sisa_aktual'),
            DB::raw('COALESCE(SUM(bills.due), 0) AS sisa_bayar'),
            DB::raw("COALESCE(SUM(CASE WHEN {$this->statusSql()} = 'lunas' THEN 1 ELSE 0 END), 0) AS siswa_lunas"),
        ])->first();
        return (array) $row;
    }

    public function row(Siswa $student): array
    {
        $class = $student->kelas;
        $name = trim((string) $class?->nama_kelas);
        $row = [
            'id' => $student->id, 'nama' => $student->nama, 'nis' => $student->nis,
            'initial' => mb_strtoupper(mb_substr($student->nama, 0, 1)),
            'school_name' => $student->sekolah?->nama_sekolah ?? 'Sekolah belum diatur',
            'class_name' => !$class ? 'Kelas belum diatur' : (in_array($name, ['', '-', '–'], true) ? $class->label_tingkat : $class->label_tingkat.' · '.$name),
            'status' => $student->workspace_status,
        ];
        foreach (['total_tagihan', 'total_nominal', 'total_dibayar', 'total_diskon', 'sisa_aktual', 'sisa_bayar'] as $field) {
            $row[$field] = (float) $student->getAttribute($field);
        }
        $row['total_tagihan'] = (int) $row['total_tagihan'];
        return $row;
    }

    private function configuredPeriods($query): void
    {
        $reconciler = app(TagihanPeriodReconciler::class);
        $types = JenisPembayaran::with('tahunAjaran')->get();
        $query->where(function ($allowed) use ($types, $reconciler) {
            $allowed->whereDoesntHave('jenisPembayaran');
            foreach ($types as $type) {
                $periods = $reconciler->expectedPeriods($type);
                $allowed->orWhere(function ($part) use ($type, $periods) {
                    $part->where('tagihan.jenis_pembayaran_id', $type->id);
                    if ($type->tipe !== 'sekali') {
                        if ($periods) $part->whereIn('tagihan.periode', $periods);
                        return;
                    }
                    $part->where(function ($period) use ($type, $periods) {
                        if ($type->tahun_ajaran_id !== null) $period->where('tagihan.tahun_ajaran_id', $type->tahun_ajaran_id);
                        $fallback = function ($old) use ($type, $periods) {
                            if ($type->tahun_ajaran_id !== null) $old->whereNull('tagihan.tahun_ajaran_id');
                            if ($periods) $old->where(fn ($p) => $p->where('tagihan.periode', $periods[0])->orWhere('tagihan.periode', 'like', $periods[0].'-%'));
                        };
                        if ($type->tahun_ajaran_id !== null) $period->orWhere($fallback);
                        else $fallback($period);
                    });
                });
            }
        });
    }
}
