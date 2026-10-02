<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('spr');
    }

    public function down(): void
    {
        throw new RuntimeException('Restore tabel spr beserta datanya dari backup sebelum migrasi.');
    }
};
