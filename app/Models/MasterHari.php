<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHari extends Model
{
    use HasFactory;

    protected $table = 'master_hari';
    protected $fillable = ['nama_hari', 'max_jam', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
        'max_jam'   => 'integer',
    ];

    public static function getActiveDays()
    {
        return self::where('is_active', true)->get();
    }

    public function waktuHaris()
    {
        return $this->hasMany(WaktuHari::class)->orderBy('jam_ke', 'asc');
    }

    // --- TAMBAHAN: Relasi ke tabel Jadwal ---
    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'master_hari_id');
    }
}