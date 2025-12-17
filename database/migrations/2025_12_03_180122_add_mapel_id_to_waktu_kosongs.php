<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('waktu_kosongs', function (Blueprint $table) {
            // 1. Cek dulu apakah kolom mapel_id SUDAH ada. Jika belum, baru buat.
            if (!Schema::hasColumn('waktu_kosongs', 'mapel_id')) {
                $table->foreignId('mapel_id')->nullable()->after('guru_id')->constrained('mapels')->onDelete('cascade');
            }

            // 2. Ubah kolom guru_id agar boleh kosong (nullable)
            // Kita jalankan ini di luar 'if' untuk memastikan atributnya benar-benar berubah jadi nullable
            $table->unsignedBigInteger('guru_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('waktu_kosongs', function (Blueprint $table) {
            // Cek sebelum menghapus
            if (Schema::hasColumn('waktu_kosongs', 'mapel_id')) {
                $table->dropForeign(['mapel_id']);
                $table->dropColumn('mapel_id');
            }
            
            // Kembalikan guru_id jadi wajib (warning: bisa error jika ada data null)
            $table->unsignedBigInteger('guru_id')->nullable(false)->change();
        });
    }
};