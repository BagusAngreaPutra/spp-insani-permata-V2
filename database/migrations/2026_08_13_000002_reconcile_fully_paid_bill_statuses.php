<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE tagihan AS t
            INNER JOIN (
                SELECT
                    tagihan_id,
                    SUM(jumlah_bayar + COALESCE(diskon, 0)) AS total_coverage
                FROM pembayaran
                GROUP BY tagihan_id
            ) AS payment_totals ON payment_totals.tagihan_id = t.id
            SET t.status = 'lunas'
            WHERE t.status <> 'lunas'
              AND payment_totals.total_coverage >= t.nominal
        SQL);
    }

    public function down(): void
    {
        // Status yang sudah direkonsiliasi tidak dapat dibedakan secara aman
        // dari status lunas yang memang sudah benar sebelum migrasi.
    }
};
