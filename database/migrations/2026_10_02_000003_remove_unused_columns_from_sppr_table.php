<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sppr', function (Blueprint $table) {
            $table->dropColumn([
                'promo',
                'perubahan_posisi',
                'biaya_surat_surat',
                'biaya_kelebihan_tanah',
                'biaya_sudut',
                'biaya_lain_lain',
                'cicilan_per_bulan',
                'jumlah_booking_fee',
                'keterangan_booking',
                'nominal_biaya_posisi_unit',
                'keterangan_posisi_unit',
                'nominal_biaya_kpr',
                'keterangan_kpr',
                'nominal_blokir_angsuran',
                'keterangan_blokir_angsuran',
                'nominal_biaya_materai',
                'keterangan_materai',
                'nominal_biaya_buka_tabungan',
                'keterangan_tabungan',
                'peningkatan_mutu',
                'keterangan_shm',
                'agama',
                'total_yang_harus_dibayar',
            ]);
        });
    }

    public function down(): void
    {
        // Recreate the columns; historical values require the pre-migration backup.
        Schema::table('sppr', function (Blueprint $table) {
            $table->text('promo')->nullable();
            $table->text('perubahan_posisi')->nullable();
            $table->bigInteger('biaya_surat_surat')->nullable();
            $table->bigInteger('biaya_kelebihan_tanah')->nullable();
            $table->bigInteger('biaya_sudut')->nullable();
            $table->bigInteger('biaya_lain_lain')->nullable();
            $table->bigInteger('cicilan_per_bulan')->nullable();
            $table->bigInteger('jumlah_booking_fee')->nullable();
            $table->string('keterangan_booking', 100)->nullable();
            $table->bigInteger('nominal_biaya_posisi_unit')->nullable();
            $table->string('keterangan_posisi_unit', 100)->nullable();
            $table->bigInteger('nominal_biaya_kpr')->nullable();
            $table->string('keterangan_kpr', 100)->nullable();
            $table->bigInteger('nominal_blokir_angsuran')->nullable();
            $table->string('keterangan_blokir_angsuran', 100)->nullable();
            $table->bigInteger('nominal_biaya_materai')->nullable();
            $table->string('keterangan_materai', 100)->nullable();
            $table->bigInteger('nominal_biaya_buka_tabungan')->nullable();
            $table->string('keterangan_tabungan', 100)->nullable();
            $table->bigInteger('peningkatan_mutu')->nullable();
            $table->string('keterangan_shm', 100)->nullable();
            $table->string('agama', 50)->nullable();
            $table->bigInteger('total_yang_harus_dibayar')->nullable();
        });
    }
};
