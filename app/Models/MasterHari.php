<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHari extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'master_hari';

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'nama_hari',
        'max_jam',
        'is_active'
    ];

    // Konversi tipe data otomatis
    protected $casts = [
        'is_active' => 'boolean',
        'max_jam'   => 'integer',
    ];

    // Helper: Ambil hari yang statusnya aktif saja
    public static function getActiveDays()
    {
        return self::where('is_active', true)->get();
    }
}