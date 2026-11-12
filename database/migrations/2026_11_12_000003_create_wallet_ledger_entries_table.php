<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_journal_id')->constrained('wallet_journals')->cascadeOnDelete();
            $table->foreignId('wallet_account_id')->nullable()->constrained('wallet_accounts')->nullOnDelete();
            $table->string('account_code', 50);
            $table->string('direction', 10);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_account_id', 'created_at']);
            $table->index(['account_code', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
