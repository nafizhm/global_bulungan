<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->json('sptb_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->dropColumn('sptb_data');
        });
    }
};
