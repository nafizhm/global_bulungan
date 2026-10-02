<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun_bangunan')->nullable()->after('luas_bangunan');
        });
    }

    public function down(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->dropColumn('tahun_bangunan');
        });
    }
};
