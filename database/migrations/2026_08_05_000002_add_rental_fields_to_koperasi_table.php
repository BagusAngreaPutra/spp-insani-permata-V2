<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('koperasi', 'jenis_transaksi')) {
            Schema::table('koperasi', function (Blueprint $table) {
                $table->enum('jenis_transaksi', ['jual', 'sewa'])
                    ->default('jual')
                    ->after('kategori');
            });
        }

        if (! Schema::hasColumn('koperasi', 'harga_sewa_siswa')) {
            Schema::table('koperasi', function (Blueprint $table) {
                $table->decimal('harga_sewa_siswa', 12, 2)
                    ->nullable()
                    ->after('harga_jual');
            });
        }

        if (! Schema::hasColumn('koperasi', 'harga_sewa_umum')) {
            Schema::table('koperasi', function (Blueprint $table) {
                $table->decimal('harga_sewa_umum', 12, 2)
                    ->nullable()
                    ->after('harga_sewa_siswa');
            });
        }

        if (! Schema::hasColumn('koperasi', 'harga_penggantian')) {
            Schema::table('koperasi', function (Blueprint $table) {
                $table->decimal('harga_penggantian', 12, 2)
                    ->nullable()
                    ->after('harga_sewa_umum');
            });
        }

        $hasIndex = collect(Schema::getIndexes('koperasi'))
            ->contains(fn (array $index): bool => $index['name'] === 'koperasi_jenis_transaksi_status_index');

        if (! $hasIndex) {
            Schema::table('koperasi', function (Blueprint $table) {
                $table->index(['jenis_transaksi', 'status'], 'koperasi_jenis_transaksi_status_index');
            });
        }
    }

    public function down(): void
    {
        $hasIndex = collect(Schema::getIndexes('koperasi'))
            ->contains(fn (array $index): bool => $index['name'] === 'koperasi_jenis_transaksi_status_index');

        Schema::table('koperasi', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex) {
                $table->dropIndex('koperasi_jenis_transaksi_status_index');
            }

            foreach (['jenis_transaksi', 'harga_sewa_siswa', 'harga_sewa_umum', 'harga_penggantian'] as $column) {
                if (Schema::hasColumn('koperasi', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
