<?php

namespace App\Services;

use App\Models\JenisPembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class TagihanPeriodReconciler
{
    public function reconcileJenis(JenisPembayaran $jenis): array
    {
        $jenis->loadMissing(['tahunAjaran', 'siswa', 'kelas']);
        $eligibleSiswa = $jenis->getEligibleSiswa();
        $eligibleIds = $eligibleSiswa->pluck('id')->map(fn ($id) => (int) $id)->all();

        $staleTargetQuery = Tagihan::where('jenis_pembayaran_id', $jenis->id);

        if ($eligibleIds) {
            $staleTargetQuery->whereNotIn('siswa_id', $eligibleIds);
        }

        $updated = 0;
        if (Schema::hasColumn('tagihan', 'is_active')) {
            $updated += (clone $staleTargetQuery)
                ->whereHas('pembayaran')
                ->update(['is_active' => false]);
        }

        $removed = (clone $staleTargetQuery)->whereDoesntHave('pembayaran')->delete();

        foreach ($eligibleSiswa as $siswa) {
            $result = $this->reconcileForStudent($jenis, $siswa, true);
            $removed += $result['removed'];
            $updated += $result['updated'];
        }

        return compact('removed', 'updated');
    }

    public function reconcileForStudent(
        JenisPembayaran $jenis,
        Siswa $siswa,
        bool $knownEligible = false
    ): array {
        $jenis->loadMissing('tahunAjaran');

        if (!$knownEligible && !$jenis->isStudentEligible($siswa->id)) {
            $ineligibleQuery = Tagihan::where('jenis_pembayaran_id', $jenis->id)
                ->where('siswa_id', $siswa->id);
            $updated = 0;

            if (Schema::hasColumn('tagihan', 'is_active')) {
                $updated = (clone $ineligibleQuery)
                    ->whereHas('pembayaran')
                    ->update(['is_active' => false]);
            }

            $removed = (clone $ineligibleQuery)
                ->where('siswa_id', $siswa->id)
                ->whereDoesntHave('pembayaran')
                ->delete();

            return ['removed' => $removed, 'updated' => $updated];
        }

        $expectedPeriods = $this->expectedPeriods($jenis);

        if (!$expectedPeriods) {
            return ['removed' => 0, 'updated' => 0];
        }

        $bills = Tagihan::where('jenis_pembayaran_id', $jenis->id)
            ->where('siswa_id', $siswa->id)
            ->withCount('pembayaran')
            ->orderBy('id')
            ->get();

        if ($jenis->tipe === 'sekali') {
            return $this->reconcileOneTimeBills($jenis, $bills, $expectedPeriods[0]);
        }

        $removed = 0;
        $obsoleteIds = $bills
            ->filter(fn (Tagihan $tagihan) =>
                !in_array((string) $tagihan->periode, $expectedPeriods, true)
                && (int) $tagihan->pembayaran_count === 0
            )
            ->pluck('id');

        if ($obsoleteIds->isNotEmpty()) {
            $removed += Tagihan::whereIn('id', $obsoleteIds)->delete();
        }

        foreach ($bills->whereIn('periode', $expectedPeriods)->groupBy('periode') as $periodBills) {
            if ($periodBills->count() < 2) {
                continue;
            }

            $paidBills = $periodBills->filter(fn (Tagihan $tagihan) => (int) $tagihan->pembayaran_count > 0);
            $keeper = $paidBills->first() ?? $periodBills->first();
            $duplicateIds = $periodBills
                ->reject(fn (Tagihan $tagihan) => $tagihan->id === $keeper->id)
                ->filter(fn (Tagihan $tagihan) => (int) $tagihan->pembayaran_count === 0)
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                $removed += Tagihan::whereIn('id', $duplicateIds)->delete();
            }
        }

        return ['removed' => $removed, 'updated' => 0];
    }

    public function expectedPeriods(JenisPembayaran $jenis): array
    {
        $bounds = $this->academicYearBounds($jenis);

        if (!$bounds) {
            return [];
        }

        [$startYear, $endYear] = $bounds;

        if ($jenis->tipe === 'bulanan') {
            $firstMonth = Carbon::create($startYear, 7, 1);

            return array_map(
                fn (int $offset) => $firstMonth->copy()->addMonths($offset)->format('Y-m'),
                range(0, 11)
            );
        }

        if ($jenis->tipe === 'semester') {
            $selectedMonth = Carbon::parse($jenis->jatuh_tempo)->month;
            $firstMonth = $selectedMonth >= 7 ? $selectedMonth : $selectedMonth + 6;
            $secondMonth = (($firstMonth + 5) % 12) + 1;

            return [
                sprintf('%04d-%02d', $startYear, $firstMonth),
                sprintf('%04d-%02d', $endYear, $secondMonth),
            ];
        }

        return [(string) $startYear];
    }

    public function belongsToConfiguredPeriod(Tagihan $tagihan): bool
    {
        $tagihan->loadMissing('jenisPembayaran.tahunAjaran');

        if (!$tagihan->jenisPembayaran) {
            return true;
        }

        $expectedPeriods = $this->expectedPeriods($tagihan->jenisPembayaran);

        // Tagihan sekali bayar versi lama menyimpan periode sebagai YYYY-MM,
        // sedangkan format saat ini hanya YYYY. Tahun ajaran adalah acuan yang
        // lebih aman karena tagihan yang sudah dibayar tidak boleh menghilang
        // hanya akibat perbedaan format periode.
        if ($tagihan->jenisPembayaran->tipe === 'sekali') {
            if ($tagihan->tahun_ajaran_id !== null
                && $tagihan->jenisPembayaran->tahun_ajaran_id !== null) {
                return (int) $tagihan->tahun_ajaran_id
                    === (int) $tagihan->jenisPembayaran->tahun_ajaran_id;
            }

            $configuredYear = $expectedPeriods[0] ?? null;
            $period = (string) $tagihan->periode;

            return $configuredYear === null
                || $period === $configuredYear
                || str_starts_with($period, $configuredYear . '-');
        }

        return !$expectedPeriods
            || in_array((string) $tagihan->periode, $expectedPeriods, true);
    }

    private function reconcileOneTimeBills(
        JenisPembayaran $jenis,
        Collection $bills,
        string $expectedPeriod
    ): array
    {
        if ($bills->isEmpty()) {
            return ['removed' => 0, 'updated' => 0];
        }

        $paidBills = $bills->filter(fn (Tagihan $tagihan) => (int) $tagihan->pembayaran_count > 0);

        if ($paidBills->isNotEmpty()) {
            $unpaidIds = $bills
                ->filter(fn (Tagihan $tagihan) => (int) $tagihan->pembayaran_count === 0)
                ->pluck('id');

            $removed = $unpaidIds->isEmpty() ? 0 : Tagihan::whereIn('id', $unpaidIds)->delete();

            return ['removed' => $removed, 'updated' => 0];
        }

        $keeper = $bills->first();
        $duplicateIds = $bills->skip(1)->pluck('id');
        $removed = $duplicateIds->isEmpty() ? 0 : Tagihan::whereIn('id', $duplicateIds)->delete();
        [$startYear, $endYear] = $this->academicYearBounds($jenis);
        $keeper->update([
            'periode' => $expectedPeriod,
            'nama_tagihan' => $jenis->nama_pembayaran . ' - Tahun Ajaran ' . $startYear . '/' . $endYear,
        ]);

        return ['removed' => $removed, 'updated' => 1];
    }

    private function academicYearBounds(JenisPembayaran $jenis): ?array
    {
        return $jenis->tahunAjaran?->periodBounds();
    }
}
