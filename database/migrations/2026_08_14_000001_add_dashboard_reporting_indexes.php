<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing(
            'pembayaran',
            'pembayaran_tanggal_siswa_index',
            fn (Blueprint $table) => $table->index(
                ['tanggal_bayar', 'siswa_id'],
                'pembayaran_tanggal_siswa_index'
            )
        );

        $this->addIndexIfMissing(
            'pemasukan',
            'pemasukan_tanggal_sekolah_index',
            fn (Blueprint $table) => $table->index(
                ['tanggal', 'sekolah_id'],
                'pemasukan_tanggal_sekolah_index'
            )
        );

        $this->addIndexIfMissing(
            'pengeluaran',
            'pengeluaran_tanggal_sekolah_index',
            fn (Blueprint $table) => $table->index(
                ['tanggal', 'sekolah_id'],
                'pengeluaran_tanggal_sekolah_index'
            )
        );

        $this->addIndexIfMissing(
            'koperasi_penjualan',
            'koperasi_penjualan_tanggal_sekolah_index',
            fn (Blueprint $table) => $table->index(
                ['tanggal', 'sekolah_id'],
                'koperasi_penjualan_tanggal_sekolah_index'
            )
        );
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('pembayaran', 'pembayaran_tanggal_siswa_index');
        $this->dropIndexIfPresent('pemasukan', 'pemasukan_tanggal_sekolah_index');
        $this->dropIndexIfPresent('pengeluaran', 'pengeluaran_tanggal_sekolah_index');
        $this->dropIndexIfPresent('koperasi_penjualan', 'koperasi_penjualan_tanggal_sekolah_index');
    }

    private function addIndexIfMissing(string $tableName, string $indexName, callable $callback): void
    {
        if (! Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, $callback);
    }

    private function dropIndexIfPresent(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        foreach (Schema::getIndexes($tableName) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
