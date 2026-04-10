<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterWaktu extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'master_waktu';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'jam_ke',
        'waktu_mulai',
        'waktu_selesai',
        'tipe'
    ];

    // Konversi tipe data otomatis
    protected $casts = [
        'jam_ke' => 'integer',
    ];

    // Helper: Ambil data urut dari jam pertama
    public static function getOrdered()
    {
        return self::orderBy('jam_ke', 'asc')->get();
    }
}