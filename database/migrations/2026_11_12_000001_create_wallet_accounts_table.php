<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 20)->default('active');
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->decimal('held_balance', 15, 2)->default(0);
            $table->decimal('daily_topup_limit', 15, 2)->nullable();
            $table->decimal('daily_payment_limit', 15, 2)->nullable();
            $table->timestamps();

            $table->unique('siswa_id');
            $table->index(['sekolah_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_accounts');
    }
};
