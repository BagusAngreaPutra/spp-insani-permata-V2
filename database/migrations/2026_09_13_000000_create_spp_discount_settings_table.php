<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('spp_discount_settings')) {
            Schema::create('spp_discount_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('nominal', 15, 2)->default(25000);
                $table->date('berlaku_mulai')->nullable();
                $table->date('berlaku_sampai')->nullable();
                $table->boolean('aktif')->default(true);
                $table->foreignId('updated_by')->nullable()->constrained('admin')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! DB::table('spp_discount_settings')->exists()) {
            DB::table('spp_discount_settings')->insert([
                'nominal' => 25000,
                'berlaku_mulai' => null,
                'berlaku_sampai' => null,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_discount_settings');
    }
};
