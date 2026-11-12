<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->date('reconciliation_date');
            $table->string('external_reference', 150)->nullable();
            $table->string('internal_reference', 100)->nullable();
            $table->decimal('external_amount', 15, 2)->nullable();
            $table->decimal('internal_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('unmatched');
            $table->json('details')->nullable();
            $table->foreignId('resolved_by_admin_id')->nullable()->constrained('admin')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'reconciliation_date', 'status']);
            $table->index(['external_reference', 'internal_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_reconciliations');
    }
};
