<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
