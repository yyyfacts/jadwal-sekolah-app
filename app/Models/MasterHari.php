<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHari extends Model
{
    use HasFactory;

    // Mendefinisikan nama tabel secara eksplisit
    protected $table = 'master_hari';

    protected $fillable = ['nama_hari', 'max_jam', 'is_active'];

    // Cast agar 'is_active' otomatis jadi boolean, dan 'max_jam' jadi integer
    protected $casts = [
        'is_active' => 'boolean',
        'max_jam'   => 'integer',
    ];

    /**
     * Helper statis untuk mengambil semua hari yang statusnya aktif (masuk sekolah).
     * Cara pakainya di Controller: MasterHari::getActiveDays()
     */
    public static function getActiveDays()
    {
        return self::where('is_active', true)->get();
    }
}