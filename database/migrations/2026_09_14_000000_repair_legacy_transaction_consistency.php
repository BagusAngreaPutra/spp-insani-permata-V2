<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan')
            || ! Schema::hasTable('pembayaran')
            || ! Schema::hasTable('siswa')
            || ! Schema::hasTable('jenis_pembayaran')
            || ! Schema::hasColumn('tagihan', 'is_active')) {
            return;
        }

        DB::transaction(function (): void {
            DB::statement(<<<'SQL'
                UPDATE tagihan
                SET id_sekolah = (
                    SELECT siswa.id_sekolah
                    FROM siswa
                    WHERE siswa.id = tagihan.siswa_id
                )
                WHERE EXISTS (
                    SELECT 1
                    FROM siswa
                    WHERE siswa.id = tagihan.siswa_id
                      AND siswa.id_sekolah IS NOT NULL
                      AND (tagihan.id_sekolah IS NULL OR tagihan.id_sekolah <> siswa.id_sekolah)
                )
            SQL);

            DB::statement(<<<'SQL'
                UPDATE pembayaran
                SET siswa_id = (
                        SELECT tagihan.siswa_id
                        FROM tagihan
                        WHERE tagihan.id = pembayaran.tagihan_id
                    ),
                    sekolah_id = (
                        SELECT tagihan.id_sekolah
                        FROM tagihan
                        WHERE tagihan.id = pembayaran.tagihan_id
                    )
                WHERE EXISTS (
                    SELECT 1
                    FROM tagihan
                    WHERE tagihan.id = pembayaran.tagihan_id
                      AND (
                          pembayaran.siswa_id <> tagihan.siswa_id
                          OR pembayaran.sekolah_id IS NULL
                          OR pembayaran.sekolah_id <> tagihan.id_sekolah
                      )
                )
            SQL);

            DB::statement(<<<'SQL'
                UPDATE tagihan
                SET status = CASE
                    WHEN COALESCE((
                        SELECT SUM(pembayaran.jumlah_bayar + COALESCE(pembayaran.diskon, 0))
                        FROM pembayaran
                        WHERE pembayaran.tagihan_id = tagihan.id
                    ), 0) + 0.01 >= tagihan.nominal THEN 'lunas'
                    ELSE 'belum'
                END
            SQL);

            DB::table('tagihan')
                ->whereNull('jenis_pembayaran_id')
                ->update(['is_active' => false]);

            DB::statement(<<<'SQL'
                UPDATE tagihan
                SET is_active = 0
                WHERE EXISTS (
                    SELECT 1
                    FROM jenis_pembayaran
                    WHERE jenis_pembayaran.id = tagihan.jenis_pembayaran_id
                      AND (
                          jenis_pembayaran.sekolah_id <> tagihan.id_sekolah
                          OR (
                              jenis_pembayaran.target_type = 'specific_students'
                              AND NOT EXISTS (
                                  SELECT 1
                                  FROM jenis_pembayaran_siswa
                                  WHERE jenis_pembayaran_siswa.jenis_pembayaran_id = jenis_pembayaran.id
                                    AND jenis_pembayaran_siswa.siswa_id = tagihan.siswa_id
                              )
                          )
                          OR (
                              jenis_pembayaran.target_type = 'specific_classes'
                              AND NOT EXISTS (
                                  SELECT 1
                                  FROM siswa
                                  JOIN jenis_pembayaran_kelas
                                    ON jenis_pembayaran_kelas.kelas_id = siswa.kelas_id
                                  WHERE siswa.id = tagihan.siswa_id
                                    AND jenis_pembayaran_kelas.jenis_pembayaran_id = jenis_pembayaran.id
                              )
                          )
                      )
                )
            SQL);
        });
    }

    public function down(): void
    {
        // Relasi yang dilengkapi dan status historis yang diselaraskan tidak dibalik.
    }
};
