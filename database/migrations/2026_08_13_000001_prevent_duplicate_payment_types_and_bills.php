<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // Data hosting tidak boleh diubah oleh migrasi ini. Indeks tagihan hanya
        // dipasang bila data yang sudah ada memang bebas duplikat; bila belum,
        // aplikasi tetap berjalan dan validasi pada controller tetap aktif.
        if (! $this->hasIndex('tagihan', self::BILL_INDEX) && ! $this->hasDuplicateBills()) {
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

    private function hasDuplicateBills(): bool
    {
        return \Illuminate\Support\Facades\DB::table('tagihan')
            ->whereNotNull('jenis_pembayaran_id')
            ->selectRaw('1')
            ->groupBy(['siswa_id', 'jenis_pembayaran_id', 'periode'])
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['name'] === $indexName);
    }
};
