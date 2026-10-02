<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->date('tanggal_sppr')->nullable()->after('no_sppr');
        });
    }

    public function down(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->dropColumn('tanggal_sppr');
        });
    }
};
