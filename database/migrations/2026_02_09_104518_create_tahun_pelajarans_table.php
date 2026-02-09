<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tahun_pelajarans', function (Blueprint $table) {
        $table->id();
        $table->string('tahun'); // Contoh: "2025/2026"
        $table->string('semester'); // Contoh: "Ganjil" atau "Genap"
        $table->boolean('is_active')->default(false); // Penanda tahun aktif
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahun_pelajarans');
    }
};