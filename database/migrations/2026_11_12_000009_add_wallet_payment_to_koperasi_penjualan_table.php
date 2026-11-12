<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('koperasi_penjualan', 'wallet_payment_id')) {
            Schema::table('koperasi_penjualan', function (Blueprint $table) {
                $table->foreignId('wallet_payment_id')->nullable()->after('metode_bayar')->constrained('wallet_payments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('koperasi_penjualan', 'wallet_payment_id')) {
            Schema::table('koperasi_penjualan', function (Blueprint $table) {
                $table->dropForeign(['wallet_payment_id']);
                $table->dropColumn('wallet_payment_id');
            });
        }
    }
};
