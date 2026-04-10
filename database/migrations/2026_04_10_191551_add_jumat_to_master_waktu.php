<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('master_waktu', function (Blueprint $table) {
            $table->time('mulai_jumat')->nullable()->after('tipe');
            $table->time('selesai_jumat')->nullable()->after('mulai_jumat');
            $table->string('tipe_jumat', 50)->default('Belajar')->after('selesai_jumat');
        });
    }

    public function down()
    {
        Schema::table('master_waktu', function (Blueprint $table) {
            $table->dropColumn(['mulai_jumat', 'selesai_jumat', 'tipe_jumat']);
        });
    }
};