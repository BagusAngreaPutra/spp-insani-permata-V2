<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_journals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 80)->unique();
            $table->string('type', 40);
            $table->string('status', 20)->default('pending');
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_journals');
    }
};
