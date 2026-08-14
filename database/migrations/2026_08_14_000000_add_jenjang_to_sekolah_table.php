<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sekolah') && ! Schema::hasColumn('sekolah', 'jenjang')) {
            Schema::table('sekolah', function (Blueprint $table): void {
                $table->string('jenjang', 100)->nullable()->after('kode_sekolah');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sekolah') && Schema::hasColumn('sekolah', 'jenjang')) {
            Schema::table('sekolah', function (Blueprint $table): void {
                $table->dropColumn('jenjang');
            });
        }
    }
};
