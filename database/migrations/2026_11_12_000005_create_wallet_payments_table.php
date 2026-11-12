<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_account_id')->constrained('wallet_accounts')->cascadeOnDelete();
            $table->foreignId('wallet_journal_id')->nullable()->constrained('wallet_journals')->nullOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 120)->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['wallet_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_payments');
    }
};
