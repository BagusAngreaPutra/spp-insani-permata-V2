<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pembayaran', 'tanggal_kwitansi')) {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->date('tanggal_kwitansi')->nullable()->after('tanggal_bayar');
            });
        }

        DB::table('pembayaran')
            ->whereNull('tanggal_kwitansi')
            ->update(['tanggal_kwitansi' => DB::raw('tanggal_bayar')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembayaran', 'tanggal_kwitansi')) {
            Schema::table('pembayaran', function (Blueprint $table) {
                $table->dropColumn('tanggal_kwitansi');
            });
        }
    }
};
