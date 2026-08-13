<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUBMISSION_INDEX = 'jp_submission_token_unique';

    private const BILL_INDEX = 'tagihan_student_payment_period_unique';

    public function up(): void
    {
        if (! Schema::hasTable('jenis_pembayaran') || ! Schema::hasTable('tagihan')) {
            return;
        }

        DB::transaction(function (): void {
            $this->mergeIdenticalPaymentTypes();
            $this->mergeDuplicateBills();
        });

        if (! Schema::hasColumn('jenis_pembayaran', 'submission_token')) {
            Schema::table('jenis_pembayaran', function (Blueprint $table): void {
                $table->uuid('submission_token')->nullable()->after('target_type');
            });
        }

        if (! $this->hasIndex('jenis_pembayaran', self::SUBMISSION_INDEX)) {
            Schema::table('jenis_pembayaran', function (Blueprint $table): void {
                $table->unique('submission_token', self::SUBMISSION_INDEX);
            });
        }

        if (! $this->hasIndex('tagihan', self::BILL_INDEX)) {
            Schema::table('tagihan', function (Blueprint $table): void {
                $table->unique(
                    ['siswa_id', 'jenis_pembayaran_id', 'periode'],
                    self::BILL_INDEX
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tagihan') && $this->hasIndex('tagihan', self::BILL_INDEX)) {
            Schema::table('tagihan', function (Blueprint $table): void {
                $table->dropUnique(self::BILL_INDEX);
            });
        }

        if (Schema::hasTable('jenis_pembayaran') && Schema::hasColumn('jenis_pembayaran', 'submission_token')) {
            $hasSubmissionIndex = $this->hasIndex('jenis_pembayaran', self::SUBMISSION_INDEX);

            Schema::table('jenis_pembayaran', function (Blueprint $table) use ($hasSubmissionIndex): void {
                if ($hasSubmissionIndex) {
                    $table->dropUnique(self::SUBMISSION_INDEX);
                }

                $table->dropColumn('submission_token');
            });
        }
    }

    /**
     * Hanya gabungkan data dengan konfigurasi yang benar-benar sama. Nama yang sama
     * dengan nominal atau target berbeda tidak disentuh oleh migrasi ini.
     */
    private function mergeIdenticalPaymentTypes(): void
    {
        $identityColumns = [
            'sekolah_id',
            'tahun_ajaran_id',
            'nama_pembayaran',
            'tipe',
            'nominal',
            'jatuh_tempo',
            'target_type',
        ];

        $groups = DB::table('jenis_pembayaran')
            ->select($identityColumns)
            ->selectRaw('MIN(id) as keeper_id, COUNT(*) as duplicate_count')
            ->groupBy($identityColumns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $duplicates = DB::table('jenis_pembayaran')
                ->where('sekolah_id', $group->sekolah_id)
                ->where('tahun_ajaran_id', $group->tahun_ajaran_id)
                ->where('nama_pembayaran', $group->nama_pembayaran)
                ->where('tipe', $group->tipe)
                ->where('nominal', $group->nominal)
                ->where('jatuh_tempo', $group->jatuh_tempo)
                ->where('target_type', $group->target_type)
                ->where('id', '<>', $group->keeper_id)
                ->orderBy('id')
                ->pluck('id');

            foreach ($duplicates as $duplicateId) {
                $this->mergePivotRows(
                    'jenis_pembayaran_siswa',
                    'siswa_id',
                    (int) $group->keeper_id,
                    (int) $duplicateId
                );
                $this->mergePivotRows(
                    'jenis_pembayaran_kelas',
                    'kelas_id',
                    (int) $group->keeper_id,
                    (int) $duplicateId
                );
                $this->moveBillsToPaymentType((int) $group->keeper_id, (int) $duplicateId);

                DB::table('jenis_pembayaran')->where('id', $duplicateId)->delete();
            }
        }
    }

    private function mergePivotRows(
        string $table,
        string $relatedColumn,
        int $keeperId,
        int $duplicateId
    ): void {
        if (! Schema::hasTable($table)) {
            return;
        }

        $rows = DB::table($table)
            ->where('jenis_pembayaran_id', $duplicateId)
            ->get([$relatedColumn, 'created_at', 'updated_at']);

        foreach ($rows as $row) {
            DB::table($table)->insertOrIgnore([
                'jenis_pembayaran_id' => $keeperId,
                $relatedColumn => $row->{$relatedColumn},
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        DB::table($table)->where('jenis_pembayaran_id', $duplicateId)->delete();
    }

    private function moveBillsToPaymentType(int $keeperTypeId, int $duplicateTypeId): void
    {
        $duplicateBills = DB::table('tagihan')
            ->where('jenis_pembayaran_id', $duplicateTypeId)
            ->orderBy('id')
            ->get();

        foreach ($duplicateBills as $duplicateBill) {
            $matchingQuery = DB::table('tagihan')
                ->where('siswa_id', $duplicateBill->siswa_id)
                ->where('jenis_pembayaran_id', $keeperTypeId);

            $duplicateBill->periode === null
                ? $matchingQuery->whereNull('periode')
                : $matchingQuery->where('periode', $duplicateBill->periode);

            $matchingBill = $matchingQuery->orderBy('id')->first();

            if (! $matchingBill) {
                DB::table('tagihan')->where('id', $duplicateBill->id)->update([
                    'jenis_pembayaran_id' => $keeperTypeId,
                ]);

                continue;
            }

            $this->mergeBillInto((int) $matchingBill->id, $duplicateBill);
        }
    }

    private function mergeDuplicateBills(): void
    {
        $groups = DB::table('tagihan')
            ->whereNotNull('jenis_pembayaran_id')
            ->select(['siswa_id', 'jenis_pembayaran_id', 'periode'])
            ->selectRaw('MIN(id) as keeper_id, COUNT(*) as duplicate_count')
            ->groupBy(['siswa_id', 'jenis_pembayaran_id', 'periode'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $duplicatesQuery = DB::table('tagihan')
                ->where('siswa_id', $group->siswa_id)
                ->where('jenis_pembayaran_id', $group->jenis_pembayaran_id)
                ->where('id', '<>', $group->keeper_id);

            $group->periode === null
                ? $duplicatesQuery->whereNull('periode')
                : $duplicatesQuery->where('periode', $group->periode);

            foreach ($duplicatesQuery->orderBy('id')->get() as $duplicateBill) {
                $this->mergeBillInto((int) $group->keeper_id, $duplicateBill);
            }
        }
    }

    private function mergeBillInto(int $keeperBillId, object $duplicateBill): void
    {
        if (Schema::hasTable('pembayaran')) {
            DB::table('pembayaran')
                ->where('tagihan_id', $duplicateBill->id)
                ->update(['tagihan_id' => $keeperBillId]);
        }

        if (($duplicateBill->status ?? null) === 'lunas') {
            DB::table('tagihan')->where('id', $keeperBillId)->update(['status' => 'lunas']);
        }

        DB::table('tagihan')->where('id', $duplicateBill->id)->delete();
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['name'] === $indexName);
    }
};
