<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini nyimpen PENGECUALIAN jam per kelas.
     * Aturan: kalau kelas+hari+jam_ke GAK ADA row di sini -> dianggap 'Belajar' (normal, boleh diisi jadwal).
     * Kalau ADA row dengan tipe selain 'Belajar' -> otomatis di-skip/diblokir buat kelas itu doang
     * (kelas lain di jam yang sama tetep normal, gak kepengaruh).
     *
     * jam_ke di sini HARUS jam_ke fisik yang sama persis kaya di tabel waktu_haris
     * (yang keliatan di modal "Atur Jam & Istirahat"), BUKAN nomor urut Belajar ke berapa.
     */
    public function up(): void
    {
        Schema::create('kelas_waktu_khusus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->onDelete('cascade');

            // Ganti nama tabel di constrained() ini kalau nama tabel master hari
            // di database lu beda dari 'master_haris' (cek di DBMS lu).
            $table->foreignId('master_hari_id')
                ->constrained('master_haris')
                ->onDelete('cascade');

            $table->integer('jam_ke');

            // 'Kosong' = default paling umum dipakai (kelas emang gak ada pelajaran).
            // Tipe lain bisa nambah sendiri (misal 'Ujian', 'Ekstrakurikuler', 'Kegiatan Khusus').
            $table->string('tipe', 50)->default('Kosong');

            $table->string('keterangan')->nullable();

            $table->timestamps();

            $table->unique(['kelas_id', 'master_hari_id', 'jam_ke'], 'kelas_waktu_khusus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_waktu_khusus');
    }
};