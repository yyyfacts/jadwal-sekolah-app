<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterWaktu extends Model
{
    use HasFactory;

    // Mendefinisikan nama tabel secara eksplisit
    protected $table = 'master_waktu';

    protected $fillable = ['jam_ke', 'waktu_mulai', 'waktu_selesai', 'tipe'];

    // Cast 'jam_ke' agar otomatis terdeteksi sebagai angka/integer
    protected $casts = [
        'jam_ke' => 'integer',
    ];

    /**
     * Helper statis untuk mengambil jadwal waktu secara berurutan.
     * Cara pakainya di Controller: MasterWaktu::getOrdered()
     */
    public static function getOrdered()
    {
        return self::orderBy('jam_ke', 'asc')->get();
    }

    /**
     * Helper dinamis untuk mengecek apakah record ini adalah jam istirahat.
     * Cara pakainya saat dilooping di Blade: if($waktu->isIstirahat()) { ... }
     */
    public function isIstirahat()
    {
        return $this->tipe === 'Istirahat';
    }
}