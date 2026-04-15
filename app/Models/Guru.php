<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'gurus';

    protected $fillable = [
        'nama_guru',
        'kode_guru',
        'hari_mengajar'
    ];

    // --- TAMBAHAN: Casting agar hari_mengajar otomatis jadi Array ---
    protected $casts = [
        'hari_mengajar' => 'array',
    ];

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'guru_id');
    }
}