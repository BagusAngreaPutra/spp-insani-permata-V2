<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan') || Schema::hasColumn('tagihan', 'is_active')) {
            return;
        }

        Schema::table('tagihan', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(true)
                ->after('status')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tagihan') || ! Schema::hasColumn('tagihan', 'is_active')) {
            return;
        }

        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
