<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['pembayaran', 'koperasi_penjualan'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->string('posted_by_name')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->text('cancellation_reason')->nullable();
            });
        }
        Schema::table('koperasi_penjualan', fn (Blueprint $table) => $table->string('metode_bayar', 20)->nullable());
        Schema::create('receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('numbering_period', 20)->default('yearly');
            $table->boolean('show_officer')->default(false);
            $table->timestamps();
        });
        Schema::create('receipt_sequences', function (Blueprint $table) {
            $table->string('scope')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_sequences');
        Schema::dropIfExists('receipt_settings');
        Schema::table('koperasi_penjualan', fn (Blueprint $table) => $table->dropColumn('metode_bayar'));
        foreach (['pembayaran', 'koperasi_penjualan'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn(['deleted_at', 'posted_by', 'posted_by_name', 'cancelled_by', 'cancellation_reason']));
        }
    }
};
