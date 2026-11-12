<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_account_id')->constrained('wallet_accounts')->cascadeOnDelete();
            $table->foreignId('wallet_journal_id')->nullable()->constrained('wallet_journals')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('reference', 100)->unique();
            $table->string('gateway_payment_id', 150)->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->decimal('gateway_fee', 15, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 120)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['wallet_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
