<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterWaktu extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'master_waktu';

    // Kolom yang boleh diisi
    protected $fillable = [
        'jam_ke', 'waktu_mulai', 'waktu_selesai', 'tipe',
        'mulai_senin', 'selesai_senin', 'tipe_senin',
        'mulai_jumat', 'selesai_jumat', 'tipe_jumat'
    ];

    // Konversi tipe data otomatis
    protected $casts = [
        'jam_ke' => 'integer',
    ];

    // Helper: Ambil data urut dari waktu paling pagi (Bukan jam_ke lagi)
    public static function getOrdered()
    {
        // KUNCI SINKRONISASI: Diurutkan berdasarkan waktu_mulai (07:00, 08:00, dst)
        // Biar kalau jam_ke nya NULL (Istirahat), posisinya tetep bener di tengah-tengah jadwal
        return self::orderBy('waktu_mulai', 'asc')->get();
    }
}